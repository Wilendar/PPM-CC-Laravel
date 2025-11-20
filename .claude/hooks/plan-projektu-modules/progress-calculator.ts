/**
 * PROGRESS CALCULATOR MODULE
 *
 * Oblicza % ukończenia ETAP-ów i całego projektu
 */

import { EtapStructure, Task, TaskStatus } from './plan-parser';

export interface ProgressStats {
  total: number;
  completed: number;        // ✅
  inProgress: number;       // 🛠️
  notStarted: number;       // ❌
  blocked: number;          // ⚠️
  percentComplete: number;
}

/**
 * Calculate progress for single ETAP
 */
export function calculateEtapProgress(etap: EtapStructure): ProgressStats {
  const stats: ProgressStats = {
    total: etap.allTasks.length,
    completed: 0,
    inProgress: 0,
    notStarted: 0,
    blocked: 0,
    percentComplete: 0
  };

  for (const task of etap.allTasks) {
    switch (task.status) {
      case '✅':
        stats.completed++;
        break;
      case '🛠️':
        stats.inProgress++;
        break;
      case '❌':
        stats.notStarted++;
        break;
      case '⚠️':
        stats.blocked++;
        break;
    }
  }

  stats.percentComplete = stats.total > 0
    ? Math.round((stats.completed / stats.total) * 100)
    : 0;

  return stats;
}

/**
 * Calculate overall project progress
 */
export function calculateProjectProgress(etaps: EtapStructure[]): ProgressStats {
  const overall: ProgressStats = {
    total: 0,
    completed: 0,
    inProgress: 0,
    notStarted: 0,
    blocked: 0,
    percentComplete: 0
  };

  for (const etap of etaps) {
    overall.total += etap.allTasks.length;
    overall.completed += etap.allTasks.filter(t => t.status === '✅').length;
    overall.inProgress += etap.allTasks.filter(t => t.status === '🛠️').length;
    overall.notStarted += etap.allTasks.filter(t => t.status === '❌').length;
    overall.blocked += etap.allTasks.filter(t => t.status === '⚠️').length;
  }

  overall.percentComplete = overall.total > 0
    ? Math.round((overall.completed / overall.total) * 100)
    : 0;

  return overall;
}

/**
 * Generate progress summary text
 */
export function generateProgressSummary(stats: ProgressStats): string {
  return `${stats.percentComplete}% UKOŃCZONE (${stats.completed}/${stats.total} zadań)`;
}

/**
 * Determine ETAP status emoji based on progress
 */
export function determineEtapStatus(stats: ProgressStats): TaskStatus {
  if (stats.completed === stats.total) {
    return '✅'; // All tasks completed
  } else if (stats.inProgress > 0 || stats.completed > 0) {
    return '🛠️'; // Work in progress
  } else if (stats.blocked > 0) {
    return '⚠️'; // Has blockers
  } else {
    return '❌'; // Not started
  }
}
