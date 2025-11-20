/**
 * MARKDOWN WRITER MODULE
 *
 * Zapisuje zmiany do plików markdown Plan_Projektu
 */

import * as fs from 'fs';
import { EtapStructure, Task, TaskStatus } from './plan-parser';
import { ProgressStats } from './progress-calculator';

export interface MarkdownUpdate {
  etap: EtapStructure;
  tasksToUpdate: Task[];
  filesToAdd: Map<Task, string[]>;
  progressUpdate?: ProgressStats;
}

/**
 * Update task status in markdown
 */
export function updateTaskStatus(lines: string[], task: Task, newStatus: TaskStatus): string[] {
  const newLines = [...lines];
  const line = newLines[task.lineNumber];

  // Replace status emoji
  const oldStatus = task.status;
  const updatedLine = line.replace(oldStatus, newStatus);

  newLines[task.lineNumber] = updatedLine;

  return newLines;
}

/**
 * Add file links to task
 */
export function addFileLinksToTask(lines: string[], task: Task, files: string[]): string[] {
  const newLines = [...lines];
  const insertIndex = task.lineNumber + 1;

  // Calculate indent based on task level
  const indent = '  '.repeat(task.level + 1);

  // Generate file link lines
  const fileLinkLines = files.map(file => `${indent}└──📁 PLIK: ${file}`);

  // Insert lines
  newLines.splice(insertIndex, 0, ...fileLinkLines);

  return newLines;
}

/**
 * Update ETAP progress line
 */
export function updateEtapProgressLine(lines: string[], progressStats: ProgressStats, etapId: string): string[] {
  const newLines = [...lines];

  // Find progress line (usually contains "Status ETAPU:" or similar)
  const progressLineIndex = newLines.findIndex(line =>
    line.includes('**Status ETAPU:**') ||
    line.includes('**Status ETAP:**') ||
    line.includes('**POSTĘP IMPLEMENTACJI:**')
  );

  if (progressLineIndex === -1) {
    console.warn(`[markdown-writer] Progress line not found in ${etapId}`);
    return newLines;
  }

  // Generate new progress text
  const statusEmoji = determineStatusEmoji(progressStats);
  const progressText = `**Status ETAPU:** ${statusEmoji} **W TRAKCIE - ${progressStats.percentComplete}% UKOŃCZONE (${progressStats.completed}/${progressStats.total} zadań)**`;

  newLines[progressLineIndex] = progressText;

  return newLines;
}

/**
 * Add metadata to task (blocker, note, etc.)
 */
export function addMetadataToTask(lines: string[], task: Task, metadata: string): string[] {
  const newLines = [...lines];
  const insertIndex = task.lineNumber + 1;

  // Calculate indent
  const indent = '  '.repeat(task.level + 1);

  // Add metadata line
  newLines.splice(insertIndex, 0, `${indent}└── ${metadata}`);

  return newLines;
}

/**
 * Add timestamp to task
 */
export function addTimestampToTask(lines: string[], task: Task, agentType: string): string[] {
  const timestamp = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
  const time = new Date().toTimeString().substring(0, 5); // HH:MM
  const metadata = `🤖 Ukończone przez: ${agentType} (${timestamp} ${time})`;

  return addMetadataToTask(lines, task, metadata);
}

/**
 * Write updated ETAP file
 */
export function writeEtapFile(etap: EtapStructure, updatedLines: string[]): boolean {
  try {
    const content = updatedLines.join('\n');
    fs.writeFileSync(etap.filePath, content, 'utf8');
    console.log(`[markdown-writer] Updated ${etap.etapId}`);
    return true;
  } catch (error) {
    console.error(`[markdown-writer] Error writing ${etap.filePath}:`, error);
    return false;
  }
}

/**
 * Determine status emoji based on progress
 */
function determineStatusEmoji(stats: ProgressStats): string {
  if (stats.percentComplete === 100) {
    return '✅';
  } else if (stats.inProgress > 0 || stats.completed > 0) {
    return '🛠️';
  } else if (stats.blocked > 0) {
    return '⚠️';
  } else {
    return '❌';
  }
}

/**
 * Create backup of file before modifying
 */
export function createBackup(filePath: string): boolean {
  try {
    const backupPath = `${filePath}.backup_${Date.now()}`;
    fs.copyFileSync(filePath, backupPath);
    console.log(`[markdown-writer] Backup created: ${backupPath}`);
    return true;
  } catch (error) {
    console.error(`[markdown-writer] Error creating backup:`, error);
    return false;
  }
}
