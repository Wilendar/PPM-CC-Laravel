/**
 * PLAN PARSER MODULE
 *
 * Parsuje pliki markdown Plan_Projektu do struktury drzewa zadań
 * Wykrywa numery zadań, statusy, hierarchię i linki do plików
 */

import * as fs from 'fs';
import * as path from 'path';

export interface Task {
  id: string;              // np. "8.1.1.1.1"
  fullId: string;          // np. "ETAP_08.8.1.1.1.1"
  title: string;           // np. "Przegląd BaseLinker API v2 Documentation"
  status: TaskStatus;      // ❌, 🛠️, ✅, ⚠️
  level: number;           // głębokość w hierarchii (0 = root)
  lineNumber: number;      // numer linii w pliku markdown
  files: string[];         // linki do plików
  metadata: string[];      // dodatkowe informacje (blokery, notatki)
  children: Task[];        // podzadania
  parent?: Task;           // parent task
  rawLine: string;         // oryginalna linia markdown
}

export type TaskStatus = '❌' | '🛠️' | '✅' | '⚠️';

export interface EtapStructure {
  etapId: string;          // np. "ETAP_08"
  etapTitle: string;       // np. "INTEGRACJE Z SYSTEMAMI ERP"
  etapStatus: TaskStatus;  // status całego etapu
  filePath: string;        // ścieżka do pliku markdown
  rootTasks: Task[];       // zadania najwyższego poziomu
  allTasks: Task[];        // wszystkie zadania (flat list)
  progressPercent: number; // % ukończenia
  totalTasks: number;
  completedTasks: number;
  lines: string[];         // wszystkie linie pliku
}

const STATUS_EMOJIS: TaskStatus[] = ['❌', '🛠️', '✅', '⚠️'];

/**
 * Parse single ETAP markdown file
 */
export function parseEtapFile(filePath: string): EtapStructure | null {
  try {
    if (!fs.existsSync(filePath)) {
      console.error(`[plan-parser] File not found: ${filePath}`);
      return null;
    }

    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');

    // Extract ETAP ID from filename (e.g., "ETAP_08_ERP_Integracje.md" -> "ETAP_08")
    const filename = path.basename(filePath);
    const etapIdMatch = filename.match(/^(ETAP_\d+[a-z]?)/i);
    const etapId = etapIdMatch ? etapIdMatch[1].toUpperCase() : 'UNKNOWN';

    // Extract ETAP title from first H1 (# ❌ ETAP 08: TITLE)
    let etapTitle = 'Unknown ETAP';
    let etapStatus: TaskStatus = '❌';

    for (const line of lines) {
      const h1Match = line.match(/^#\s*([❌🛠️✅⚠️]?)\s*ETAP[_ ]?\d+[a-z]?[:\s]+(.+)/i);
      if (h1Match) {
        if (h1Match[1] && STATUS_EMOJIS.includes(h1Match[1] as TaskStatus)) {
          etapStatus = h1Match[1] as TaskStatus;
        }
        etapTitle = h1Match[2].trim();
        break;
      }
    }

    // Parse all tasks
    const allTasks: Task[] = [];
    const taskStack: Task[] = []; // Stack for building hierarchy

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const task = parseTaskLine(line, i, etapId);

      if (task) {
        // Find parent based on hierarchy level
        while (taskStack.length > 0 && taskStack[taskStack.length - 1].level >= task.level) {
          taskStack.pop();
        }

        if (taskStack.length > 0) {
          const parent = taskStack[taskStack.length - 1];
          task.parent = parent;
          parent.children.push(task);
        }

        taskStack.push(task);
        allTasks.push(task);
      }

      // Check for file links on next line
      if (task && i + 1 < lines.length) {
        const nextLine = lines[i + 1];
        const fileMatch = nextLine.match(/└──📁 PLIK:\s*(.+)/);
        if (fileMatch) {
          task.files.push(fileMatch[1].trim());
        }
      }

      // Check for metadata (blokers, notes)
      if (task && i + 1 < lines.length) {
        const nextLine = lines[i + 1];
        if (nextLine.includes('└──') && !nextLine.includes('📁 PLIK:')) {
          task.metadata.push(nextLine.trim());
        }
      }
    }

    // Build root tasks (tasks without parents)
    const rootTasks = allTasks.filter(t => !t.parent);

    // Calculate progress
    const completedTasks = allTasks.filter(t => t.status === '✅').length;
    const totalTasks = allTasks.length;
    const progressPercent = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

    return {
      etapId,
      etapTitle,
      etapStatus,
      filePath,
      rootTasks,
      allTasks,
      progressPercent,
      totalTasks,
      completedTasks,
      lines
    };
  } catch (error) {
    console.error(`[plan-parser] Error parsing ${filePath}:`, error);
    return null;
  }
}

/**
 * Parse single task line
 * Handles various formats:
 * - "- ❌ 8.1.1 Title"
 * - "#### ❌ 8.1.1.1 Title"
 * - "  - ✅ 8.1.1.1.1 Title"
 */
function parseTaskLine(line: string, lineNumber: number, etapId: string): Task | null {
  // Match markdown headers (##, ###, ####) or list items (-, *)
  const headerMatch = line.match(/^(#{1,6})\s*([❌🛠️✅⚠️]?)\s*([\d.]+)\s+(.+)/);
  const listMatch = line.match(/^(\s*)[-*]\s*([❌🛠️✅⚠️]?)\s*([\d.]+)\s+(.+)/);

  let match = headerMatch || listMatch;
  if (!match) return null;

  let level: number;
  let status: TaskStatus;
  let id: string;
  let title: string;

  if (headerMatch) {
    // Header format: "### ❌ 8.1.1 Title"
    level = headerMatch[1].length - 1; // ## = level 1, ### = level 2
    status = (headerMatch[2] as TaskStatus) || '❌';
    id = headerMatch[3];
    title = headerMatch[4].trim();
  } else {
    // List format: "  - ❌ 8.1.1.1.1 Title"
    const indent = listMatch![1].length;
    level = Math.floor(indent / 2); // 2 spaces = 1 level
    status = (listMatch![2] as TaskStatus) || '❌';
    id = listMatch![3];
    title = listMatch![4].trim();
  }

  // Validate status emoji
  if (!STATUS_EMOJIS.includes(status)) {
    status = '❌';
  }

  // Clean title (remove trailing markdown, links, etc.)
  title = title
    .replace(/\*\*(.+?)\*\*/g, '$1') // Remove bold
    .replace(/\[(.+?)\]\(.+?\)/g, '$1') // Remove links
    .trim();

  const fullId = `${etapId}.${id}`;

  return {
    id,
    fullId,
    title,
    status,
    level,
    lineNumber,
    files: [],
    metadata: [],
    children: [],
    rawLine: line
  };
}

/**
 * Parse all ETAP files in Plan_Projektu directory
 */
export function parseAllEtaps(planDir: string): EtapStructure[] {
  try {
    if (!fs.existsSync(planDir)) {
      console.error(`[plan-parser] Plan_Projektu directory not found: ${planDir}`);
      return [];
    }

    const files = fs.readdirSync(planDir);
    const etapFiles = files.filter(f => f.match(/^ETAP_\d+[a-z]?.*\.md$/i) && f !== 'README.md');

    const etaps: EtapStructure[] = [];

    for (const file of etapFiles) {
      const filePath = path.join(planDir, file);
      const etap = parseEtapFile(filePath);
      if (etap) {
        etaps.push(etap);
      }
    }

    // Sort by ETAP number
    etaps.sort((a, b) => {
      const aNum = parseInt(a.etapId.match(/\d+/)?.[0] || '0');
      const bNum = parseInt(b.etapId.match(/\d+/)?.[0] || '0');
      return aNum - bNum;
    });

    return etaps;
  } catch (error) {
    console.error('[plan-parser] Error parsing all ETAPs:', error);
    return [];
  }
}

/**
 * Find task by ID across all ETAPs
 */
export function findTaskById(etaps: EtapStructure[], taskId: string): { etap: EtapStructure; task: Task } | null {
  for (const etap of etaps) {
    const task = etap.allTasks.find(t => t.id === taskId || t.fullId === taskId);
    if (task) {
      return { etap, task };
    }
  }
  return null;
}

/**
 * Find tasks by keywords (fuzzy search)
 */
export function findTasksByKeywords(etaps: EtapStructure[], keywords: string[]): Array<{ etap: EtapStructure; task: Task; score: number }> {
  const results: Array<{ etap: EtapStructure; task: Task; score: number }> = [];

  for (const etap of etaps) {
    for (const task of etap.allTasks) {
      const score = calculateMatchScore(task, keywords);
      if (score > 0) {
        results.push({ etap, task, score });
      }
    }
  }

  // Sort by score (highest first)
  results.sort((a, b) => b.score - a.score);

  return results;
}

/**
 * Calculate match score for task based on keywords
 */
function calculateMatchScore(task: Task, keywords: string[]): number {
  let score = 0;
  const searchText = `${task.id} ${task.title}`.toLowerCase();

  for (const keyword of keywords) {
    const lowerKeyword = keyword.toLowerCase();

    // Exact match in ID (highest score)
    if (task.id.includes(lowerKeyword)) {
      score += 100;
    }

    // Exact word match in title
    const titleWords = task.title.toLowerCase().split(/\s+/);
    if (titleWords.includes(lowerKeyword)) {
      score += 50;
    }

    // Partial match in title
    if (task.title.toLowerCase().includes(lowerKeyword)) {
      score += 25;
    }

    // Fuzzy match (levenshtein distance)
    for (const word of titleWords) {
      if (word.length >= 4 && lowerKeyword.length >= 4) {
        const distance = levenshteinDistance(word, lowerKeyword);
        const similarity = 1 - distance / Math.max(word.length, lowerKeyword.length);
        if (similarity > 0.7) {
          score += Math.round(similarity * 15);
        }
      }
    }
  }

  return score;
}

/**
 * Levenshtein distance for fuzzy matching
 */
function levenshteinDistance(a: string, b: string): number {
  const matrix: number[][] = [];

  for (let i = 0; i <= b.length; i++) {
    matrix[i] = [i];
  }

  for (let j = 0; j <= a.length; j++) {
    matrix[0][j] = j;
  }

  for (let i = 1; i <= b.length; i++) {
    for (let j = 1; j <= a.length; j++) {
      if (b.charAt(i - 1) === a.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1];
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1, // substitution
          matrix[i][j - 1] + 1,     // insertion
          matrix[i - 1][j] + 1      // deletion
        );
      }
    }
  }

  return matrix[b.length][a.length];
}

/**
 * Get task path (breadcrumbs)
 * Example: "8.1 Analysis → 8.1.1 BaseLinker API → 8.1.1.1 Documentation"
 */
export function getTaskPath(task: Task): string {
  const path: string[] = [];
  let current: Task | undefined = task;

  while (current) {
    path.unshift(`${current.id} ${current.title}`);
    current = current.parent;
  }

  return path.join(' → ');
}
