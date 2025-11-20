/**
 * FILE LINKER MODULE
 *
 * Dodaje linki do plików pod zadaniami w planie
 */

import { Task } from './plan-parser';

export interface FileLinkOperation {
  task: Task;
  filesToAdd: string[];
  existingFiles: string[];
  newFiles: string[];
}

/**
 * Prepare file links to add to task
 */
export function prepareFileLinks(task: Task, modifiedFiles: string[]): FileLinkOperation {
  const existingFiles = task.files;
  const newFiles = modifiedFiles.filter(f => !existingFiles.includes(f));

  return {
    task,
    filesToAdd: newFiles,
    existingFiles,
    newFiles
  };
}

/**
 * Generate file link markdown lines
 */
export function generateFileLinkLines(files: string[], indent: string = ''): string[] {
  return files.map(file => `${indent}└──📁 PLIK: ${file}`);
}

/**
 * Check if file already linked to task
 */
export function isFileLinked(task: Task, filePath: string): boolean {
  return task.files.includes(filePath);
}

/**
 * Normalize file path for comparison
 */
export function normalizeFilePath(filePath: string): string {
  return filePath.replace(/\\/g, '/').replace(/^\/+/, '');
}
