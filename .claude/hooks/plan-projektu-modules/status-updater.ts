/**
 * STATUS UPDATER MODULE
 *
 * Zmiana statusów zadań (❌ → 🛠️ → ✅)
 */

import { Task, TaskStatus } from './plan-parser';

export interface StatusUpdate {
  task: Task;
  oldStatus: TaskStatus;
  newStatus: TaskStatus;
  reason: string;
}

/**
 * Determine new status based on work context
 */
export function determineNewStatus(task: Task, workCompleted: boolean, hasErrors: boolean): StatusUpdate {
  const oldStatus = task.status;
  let newStatus: TaskStatus = oldStatus;
  let reason = '';

  if (workCompleted && !hasErrors) {
    // Work completed successfully
    newStatus = '✅';
    reason = 'Task completed successfully by subagent';
  } else if (hasErrors) {
    // Work has blockers
    newStatus = '⚠️';
    reason = 'Task blocked due to errors';
  } else if (oldStatus === '❌') {
    // Work started
    newStatus = '🛠️';
    reason = 'Task started';
  }

  return { task, oldStatus, newStatus, reason };
}

/**
 * Check if status change is valid
 */
export function isValidStatusTransition(oldStatus: TaskStatus, newStatus: TaskStatus): boolean {
  // Valid transitions:
  // ❌ → 🛠️ → ✅
  // ❌ → ⚠️
  // 🛠️ → ✅
  // 🛠️ → ⚠️
  // ⚠️ → 🛠️ (retry)

  if (oldStatus === newStatus) return true; // No change

  const validTransitions: Record<TaskStatus, TaskStatus[]> = {
    '❌': ['🛠️', '⚠️', '✅'],
    '🛠️': ['✅', '⚠️'],
    '⚠️': ['🛠️', '✅'],
    '✅': [] // Completed tasks shouldn't change (unless manually)
  };

  return validTransitions[oldStatus]?.includes(newStatus) || false;
}
