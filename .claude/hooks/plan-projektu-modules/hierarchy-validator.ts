/**
 * HIERARCHY VALIDATOR MODULE
 *
 * Waliduje hierarchię zadań (parent może być ✅ tylko gdy wszystkie children są ✅)
 */

import { Task, TaskStatus } from './plan-parser';

export interface ValidationResult {
  isValid: boolean;
  errors: string[];
  warnings: string[];
}

/**
 * Validate if parent task can be marked as completed
 */
export function canMarkParentComplete(parent: Task): ValidationResult {
  const result: ValidationResult = {
    isValid: true,
    errors: [],
    warnings: []
  };

  if (parent.children.length === 0) {
    return result; // No children, OK to complete
  }

  // Check if all children are completed
  const incompleteChildren = parent.children.filter(c => c.status !== '✅');

  if (incompleteChildren.length > 0) {
    result.isValid = false;
    result.errors.push(
      `Task ${parent.id} cannot be marked as ✅ because ${incompleteChildren.length} child task(s) are not completed`
    );

    for (const child of incompleteChildren) {
      result.warnings.push(`- ${child.id} ${child.title} (${child.status})`);
    }
  }

  return result;
}

/**
 * Update parent status based on children
 */
export function updateParentStatus(parent: Task): TaskStatus {
  if (parent.children.length === 0) {
    return parent.status; // No children, keep current status
  }

  const allCompleted = parent.children.every(c => c.status === '✅');
  const someInProgress = parent.children.some(c => c.status === '🛠️');
  const someBlocked = parent.children.some(c => c.status === '⚠️');
  const someCompleted = parent.children.some(c => c.status === '✅');

  if (allCompleted) {
    return '✅';
  } else if (someInProgress || someCompleted) {
    return '🛠️';
  } else if (someBlocked) {
    return '⚠️';
  } else {
    return '❌';
  }
}

/**
 * Propagate status changes up the hierarchy
 */
export function propagateStatusUp(task: Task): Task[] {
  const updates: Task[] = [];
  let current = task.parent;

  while (current) {
    const newStatus = updateParentStatus(current);
    if (newStatus !== current.status) {
      updates.push({ ...current, status: newStatus });
    }
    current = current.parent;
  }

  return updates;
}
