/**
 * TASK MATCHER MODULE
 *
 * Smart dopasowanie pracy subagenta do zadań w planie
 * Używa fuzzy matching, analizy słów kluczowych i file patterns
 */

import { EtapStructure, Task, findTasksByKeywords } from './plan-parser';

export interface TaskMatch {
  task: Task;
  etap: EtapStructure;
  score: number;
  matchReasons: string[];
}

export interface SubagentWorkContext {
  initialPrompt: string;
  modifiedFiles: string[];
  tools Used: string[];
  errors: string[];
  taskDescription: string;
}

/**
 * Find best matching task for subagent work
 */
export function findBestTaskMatch(
  etaps: EtapStructure[],
  workContext: SubagentWorkContext
): TaskMatch | null {
  const allMatches: TaskMatch[] = [];

  // Extract keywords from initial prompt
  const keywords = extractKeywords(workContext.initialPrompt);

  // Find tasks by keywords
  const keywordMatches = findTasksByKeywords(etaps, keywords);

  for (const match of keywordMatches) {
    const matchReasons: string[] = [];
    let score = match.score;

    // Boost score if task status is ❌ or 🛠️ (prioritize unfinished tasks)
    if (match.task.status === '❌') {
      score += 50;
      matchReasons.push('Task not started (❌)');
    } else if (match.task.status === '🛠️') {
      score += 75;
      matchReasons.push('Task in progress (🛠️)');
    } else if (match.task.status === '✅') {
      score -= 100; // Penalize completed tasks
    }

    // Boost score if modified files match task context
    const fileMatchScore = calculateFileMatchScore(match.task, workContext.modifiedFiles);
    if (fileMatchScore > 0) {
      score += fileMatchScore;
      matchReasons.push(`File pattern match (+${fileMatchScore})`);
    }

    // Boost score if task has no files yet (needs implementation)
    if (match.task.files.length === 0) {
      score += 25;
      matchReasons.push('No files linked yet');
    }

    // Add keyword match reason
    if (match.score > 0) {
      matchReasons.push(`Keyword match (+${match.score})`);
    }

    allMatches.push({
      task: match.task,
      etap: match.etap,
      score,
      matchReasons
    });
  }

  // Sort by score
  allMatches.sort((a, b) => b.score - a.score);

  // Return best match (if score > threshold)
  const bestMatch = allMatches[0];
  if (bestMatch && bestMatch.score >= 50) {
    return bestMatch;
  }

  return null;
}

/**
 * Extract keywords from prompt
 */
function extractKeywords(prompt: string): string[] {
  // Remove common Polish words (stopwords)
  const stopwords = ['i', 'w', 'z', 'do', 'na', 'dla', 'ze', 'o', 'po', 'przy', 'od', 'za', 'przez', 'jak', 'aby'];

  const words = prompt
    .toLowerCase()
    .replace(/[^a-ząćęłńóśźż0-9\s]/gi, '')
    .split(/\s+/)
    .filter(w => w.length >= 3)
    .filter(w => !stopwords.includes(w));

  // Remove duplicates
  return [...new Set(words)];
}

/**
 * Calculate file pattern match score
 */
function calculateFileMatchScore(task: Task, modifiedFiles: string[]): number {
  let score = 0;

  // Check if task title mentions file-related keywords
  const title = task.title.toLowerCase();
  const fileKeywords = [
    'controller', 'model', 'service', 'migration', 'component', 'livewire',
    'blade', 'view', 'request', 'middleware', 'job', 'event', 'listener'
  ];

  for (const keyword of fileKeywords) {
    if (title.includes(keyword)) {
      // Check if modified files match the keyword
      for (const file of modifiedFiles) {
        const lowerFile = file.toLowerCase();
        if (lowerFile.includes(keyword)) {
          score += 30;
        }
      }
    }
  }

  // Check for specific file patterns in task ID
  // Example: "5.1.2 ProductController" → look for ProductController.php
  const idWords = task.title.split(/\s+/);
  for (const word of idWords) {
    if (word.length >= 4) {
      for (const file of modifiedFiles) {
        if (file.toLowerCase().includes(word.toLowerCase())) {
          score += 20;
        }
      }
    }
  }

  return score;
}

/**
 * Find tasks that should be auto-created (no match found)
 */
export function shouldAutoCreateTask(workContext: SubagentWorkContext, bestMatch: TaskMatch | null): boolean {
  // Auto-create if:
  // 1. No match found
  // 2. Files were modified
  // 3. Work was substantial (multiple files or tools)

  if (bestMatch && bestMatch.score >= 100) {
    return false; // Good match found
  }

  if (workContext.modifiedFiles.length === 0) {
    return false; // No files modified
  }

  if (workContext.modifiedFiles.length >= 2 || workContext.toolsUsed.length >= 3) {
    return true; // Substantial work
  }

  return false;
}

/**
 * Generate auto-created task title from work context
 */
export function generateAutoTaskTitle(workContext: SubagentWorkContext): string {
  // Try to use first 60 chars of initial prompt
  const prompt = workContext.initialPrompt.trim();

  if (prompt.length <= 60) {
    return prompt;
  }

  // Truncate and add ellipsis
  return prompt.substring(0, 57) + '...';
}
