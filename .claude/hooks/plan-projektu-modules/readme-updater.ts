/**
 * README UPDATER MODULE
 *
 * Aktualizuje Plan_Projektu/README.md z postępem projektu
 */

import * as fs from 'fs';
import * as path from 'path';
import { EtapStructure } from './plan-parser';
import { ProgressStats, calculateProjectProgress } from './progress-calculator';

/**
 * Update README.md with overall project progress
 */
export function updateReadmeProgress(planDir: string, etaps: EtapStructure[]): boolean {
  try {
    const readmePath = path.join(planDir, 'README.md');

    if (!fs.existsSync(readmePath)) {
      console.warn('[readme-updater] README.md not found');
      return false;
    }

    const content = fs.readFileSync(readmePath, 'utf8');
    const lines = content.split('\n');

    // Calculate overall progress
    const overall = calculateProjectProgress(etaps);

    // Find and update progress section
    const progressIndex = lines.findIndex(l => l.includes('## 📊 Postęp ogólny:') || l.includes('## Postęp projektu'));

    if (progressIndex !== -1) {
      lines[progressIndex] = `## 📊 Postęp ogólny: ${overall.percentComplete}% (${overall.completed}/${overall.total} zadań)`;
    }

    // Update ETAP statuses
    for (const etap of etaps) {
      const etapLineIndex = lines.findIndex(l => l.includes(etap.etapId));
      if (etapLineIndex !== -1) {
        const statusEmoji = etap.etapStatus;
        lines[etapLineIndex] = `- ${statusEmoji} ${etap.etapId} - ${etap.etapTitle} (${etap.progressPercent}%)`;
      }
    }

    // Write back
    fs.writeFileSync(readmePath, lines.join('\n'), 'utf8');
    console.log('[readme-updater] README.md updated');
    return true;
  } catch (error) {
    console.error('[readme-updater] Error updating README:', error);
    return false;
  }
}
