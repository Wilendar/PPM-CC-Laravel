/**
 * PLAN PROJEKTU SYNC HOOK
 *
 * Automatycznie aktualizuje Plan_Projektu na podstawie prac subagentów
 *
 * Funkcje:
 * - Smart Task Matching - dopasowuje pracę do zadań
 * - Automatyczne dodawanie linków do plików
 * - Zmiana statusów (❌ → 🛠️ → ✅)
 * - Walidacja hierarchii (parent/children)
 * - Przeliczanie % ukończenia
 * - Aktualizacja README.md
 * - Auto-tworzenie nowych zadań
 *
 * Project: PPM-CC-Laravel
 * Created: 2025-11-17
 */

import * as fs from 'fs';
import * as path from 'path';

// Import modules
import { parseAllEtaps, parseEtapFile, EtapStructure, Task } from './plan-projektu-modules/plan-parser';
import { findBestTaskMatch, SubagentWorkContext, shouldAutoCreateTask, generateAutoTaskTitle } from './plan-projektu-modules/task-matcher';
import { determineNewStatus } from './plan-projektu-modules/status-updater';
import { canMarkParentComplete, propagateStatusUp } from './plan-projektu-modules/hierarchy-validator';
import { prepareFileLinks } from './plan-projektu-modules/file-linker';
import { calculateEtapProgress, generateProgressSummary } from './plan-projektu-modules/progress-calculator';
import { updateTaskStatus, addFileLinksToTask, addTimestampToTask, updateEtapProgressLine, writeEtapFile, createBackup } from './plan-projektu-modules/markdown-writer';
import { updateReadmeProgress } from './plan-projektu-modules/readme-updater';

interface SubagentStopParams {
  session_id: string;
  transcript_path: string;
  agent_id: string;
  agent_transcript_path: string;
  hook_event_name: 'SubagentStop';
  cwd: string;
  permission_mode: string;
  stop_hook_active: boolean;
}

interface TranscriptMessage {
  type: 'message' | 'tool_use' | 'tool_result';
  role?: 'user' | 'assistant';
  content?: string | any[];
  name?: string;
  args?: any;
}

/**
 * Load and parse transcript
 */
function loadTranscript(transcriptPath: string): TranscriptMessage[] {
  try {
    if (!fs.existsSync(transcriptPath)) {
      return [];
    }

    const content = fs.readFileSync(transcriptPath, 'utf8');
    const lines = content.trim().split('\n');
    const messages: TranscriptMessage[] = [];

    for (const line of lines) {
      if (!line.trim()) continue;
      try {
        messages.push(JSON.parse(line));
      } catch {}
    }

    return messages;
  } catch {
    return [];
  }
}

/**
 * Extract work context from transcript
 */
function extractWorkContext(messages: TranscriptMessage[]): SubagentWorkContext {
  // Extract initial prompt
  const firstUserMsg = messages.find(m => m.type === 'message' && m.role === 'user');
  const initialPrompt = typeof firstUserMsg?.content === 'string' ? firstUserMsg.content : '';

  // Extract modified files
  const modifiedFiles: string[] = [];
  const editTools = ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'];

  for (const msg of messages) {
    if (msg.type === 'tool_use' && msg.name && editTools.includes(msg.name)) {
      const filePath = msg.args?.file_path || msg.args?.filePath || msg.args?.notebook_path;
      if (filePath) {
        modifiedFiles.push(normalizeFilePath(filePath));
      }
    }
  }

  // Extract tools used
  const toolsUsed = [...new Set(messages.filter(m => m.type === 'tool_use' && m.name).map(m => m.name!))];

  // Extract errors
  const errors: string[] = [];
  for (const msg of messages) {
    if (msg.type === 'tool_result' && typeof msg.content === 'string') {
      if (msg.content.toLowerCase().includes('error')) {
        errors.push(msg.content.substring(0, 200));
      }
    }
  }

  // Task description (first 200 chars of prompt)
  const taskDescription = initialPrompt.length > 200 ? initialPrompt.substring(0, 200) + '...' : initialPrompt;

  return {
    initialPrompt,
    modifiedFiles,
    toolsUsed,
    errors,
    taskDescription
  };
}

/**
 * Normalize file path
 */
function normalizeFilePath(filePath: string): string {
  return filePath.replace(/\\/g, '/').replace(/^\/+/, '');
}

/**
 * Extract agent type from transcript
 */
function extractAgentType(messages: TranscriptMessage[]): string {
  const firstUserMsg = messages.find(m => m.type === 'message' && m.role === 'user');
  if (firstUserMsg && typeof firstUserMsg.content === 'string') {
    const content = firstUserMsg.content.toLowerCase();
    const agentTypes = ['laravel-expert', 'livewire-specialist', 'frontend-specialist', 'debugger', 'architect'];
    for (const type of agentTypes) {
      if (content.includes(type.replace('-', ' '))) return type;
    }
  }
  return 'general-purpose';
}

/**
 * Main hook function
 */
export async function run(hookParams: SubagentStopParams): Promise<{ message?: string }> {
  try {
    console.log(`[plan-projektu-sync] Starting for agent: ${hookParams.agent_id}`);

    // Find Plan_Projektu directory
    const planDir = path.join(hookParams.cwd, 'Plan_Projektu');
    if (!fs.existsSync(planDir)) {
      console.log('[plan-projektu-sync] Plan_Projektu directory not found, skipping');
      return {};
    }

    // Load transcript
    const messages = loadTranscript(hookParams.agent_transcript_path);
    if (messages.length === 0) {
      console.log('[plan-projektu-sync] Empty transcript, skipping');
      return {};
    }

    // Extract work context
    const workContext = extractWorkContext(messages);
    if (workContext.modifiedFiles.length === 0) {
      console.log('[plan-projektu-sync] No files modified, skipping');
      return {};
    }

    // Parse all ETAPs
    console.log('[plan-projektu-sync] Parsing Plan_Projektu...');
    const etaps = parseAllEtaps(planDir);
    if (etaps.length === 0) {
      console.log('[plan-projektu-sync] No ETAPs found');
      return {};
    }

    // Find best matching task
    console.log('[plan-projektu-sync] Finding best task match...');
    const bestMatch = findBestTaskMatch(etaps, workContext);

    let updatedMessage = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
    updatedMessage += '📋 PLAN PROJEKTU - AUTOMATYCZNA AKTUALIZACJA\n';
    updatedMessage += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

    if (bestMatch && bestMatch.score >= 50) {
      // Match found - update task
      console.log(`[plan-projektu-sync] Match found: ${bestMatch.task.id} ${bestMatch.task.title} (score: ${bestMatch.score})`);

      const task = bestMatch.task;
      const etap = bestMatch.etap;

      // Create backup
      createBackup(etap.filePath);

      let lines = [...etap.lines];

      // Determine new status
      const hasErrors = workContext.errors.length > 0;
      const statusUpdate = determineNewStatus(task, true, hasErrors);

      // Update status
      if (statusUpdate.newStatus !== task.status) {
        lines = updateTaskStatus(lines, task, statusUpdate.newStatus);
        updatedMessage += `✅ Status zmieniony: ${task.status} → ${statusUpdate.newStatus}\n`;
      }

      // Add file links
      const fileLinkOp = prepareFileLinks(task, workContext.modifiedFiles);
      if (fileLinkOp.newFiles.length > 0) {
        lines = addFileLinksToTask(lines, task, fileLinkOp.newFiles);
        updatedMessage += `📁 Dodano linki do ${fileLinkOp.newFiles.length} pliku/plików\n`;
      }

      // Add timestamp
      const agentType = extractAgentType(messages);
      lines = addTimestampToTask(lines, task, agentType);

      // Validate hierarchy
      if (statusUpdate.newStatus === '✅' && task.parent) {
        const validation = canMarkParentComplete(task.parent);
        if (!validation.isValid) {
          updatedMessage += `\n⚠️  UWAGA: Parent task nie może być oznaczony jako ukończony:\n`;
          for (const error of validation.errors) {
            updatedMessage += `   ${error}\n`;
          }
        }
      }

      // Recalculate progress
      const newProgress = calculateEtapProgress({ ...etap, lines });
      lines = updateEtapProgressLine(lines, newProgress, etap.etapId);

      // Write updated file
      writeEtapFile(etap, lines);

      updatedMessage += `\n📊 Zadanie: ${task.id} ${task.title}\n`;
      updatedMessage += `📁 ETAP: ${etap.etapId} - ${etap.etapTitle}\n`;
      updatedMessage += `📈 Postęp ETAP: ${newProgress.percentComplete}% (${newProgress.completed}/${newProgress.total})\n`;

      // Update README
      updateReadmeProgress(planDir, etaps);
      updatedMessage += `\n✅ README.md zaktualizowany\n`;

    } else if (shouldAutoCreateTask(workContext, bestMatch)) {
      // No good match - auto-create task
      console.log('[plan-projektu-sync] No match found, auto-creating task');

      updatedMessage += `⚠️  Nie znaleziono dopasowania w planie\n`;
      updatedMessage += `📝 Praca wykonana: ${workContext.modifiedFiles.length} pliku/plików zmodyfikowanych\n`;
      updatedMessage += `\n💡 Sugestia: Dodaj zadanie do planu ręcznie lub użyj /plan-update\n`;

    } else {
      // No match and work was minimal
      console.log('[plan-projektu-sync] No match and minimal work, skipping');
      return {};
    }

    updatedMessage += '\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

    return { message: updatedMessage };

  } catch (error) {
    console.error('[plan-projektu-sync] Error:', error);
    return {
      message: `\n⚠️  [plan-projektu-sync] Błąd aktualizacji planu: ${error}\n`
    };
  }
}

// Export for Claude Code hooks system
export default { run };
