/**
 * POST TOOL USE HOOK
 *
 * Runs AFTER Edit/Write/MultiEdit tool usage
 * Logs edited files for build checker and skill analysis
 *
 * Based on: Reddit "Claude Code is a Beast" guide
 * Project: PPM-CC-Laravel-TEST
 * Created: 2025-11-04
 */

import * as fs from 'fs';
import * as path from 'path';

interface EditLog {
  edits: Array<{
    timestamp: string;
    file: string;
    repository: string;
    tool: string;
  }>;
}

/**
 * Determine which repository a file belongs to
 */
function getRepository(filePath: string): string {
  // PPM project is single-repo (not multi-repo like in Reddit example)
  // But we can still categorize by directory

  if (filePath.includes('/app/')) return 'main';
  if (filePath.includes('/resources/')) return 'main';
  if (filePath.includes('/database/')) return 'main';
  if (filePath.includes('/routes/')) return 'main';
  if (filePath.includes('/.claude/')) return '.claude';
  if (filePath.includes('/_DOCS/')) return '_DOCS';
  if (filePath.includes('/_TOOLS/')) return '_TOOLS';

  return 'other';
}

/**
 * Load existing edit logs
 */
function loadEditLogs(): EditLog {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const editLogsPath = path.join(projectRoot, '.claude', 'edit-logs.json');

    if (!fs.existsSync(editLogsPath)) {
      return { edits: [] };
    }

    const content = fs.readFileSync(editLogsPath, 'utf8');
    return JSON.parse(content) as EditLog;
  } catch (error) {
    console.error('[post-tool-use] Error loading edit logs:', error);
    return { edits: [] };
  }
}

/**
 * Save edit logs
 */
function saveEditLogs(logs: EditLog): void {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const editLogsPath = path.join(projectRoot, '.claude', 'edit-logs.json');

    // Ensure .claude directory exists
    const claudeDir = path.dirname(editLogsPath);
    if (!fs.existsSync(claudeDir)) {
      fs.mkdirSync(claudeDir, { recursive: true });
    }

    fs.writeFileSync(editLogsPath, JSON.stringify(logs, null, 2), 'utf8');
  } catch (error) {
    console.error('[post-tool-use] Error saving edit logs:', error);
  }
}

/**
 * Cleanup old edit logs (keep last 24 hours)
 */
function cleanupOldLogs(logs: EditLog): EditLog {
  const twentyFourHoursAgo = Date.now() - (24 * 60 * 60 * 1000);

  const recentEdits = logs.edits.filter(edit => {
    const editTime = new Date(edit.timestamp).getTime();
    return editTime > twentyFourHoursAgo;
  });

  return { edits: recentEdits };
}

/**
 * Main hook function
 */
export async function run(hookParams: {
  tool: string;
  args: any;
  result: any;
  context: any;
}): Promise<void> {
  try {
    const { tool, args } = hookParams;

    // Only log Edit, Write, MultiEdit, NotebookEdit tools
    const editTools = ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'];
    if (!editTools.includes(tool)) {
      return;
    }

    // Get file path
    let filePath: string | null = null;

    if (tool === 'Edit' || tool === 'Write') {
      filePath = args.file_path || args.filePath || null;
    } else if (tool === 'MultiEdit') {
      // MultiEdit can edit multiple files
      // For now, just log the first file
      if (args.edits && args.edits.length > 0) {
        filePath = args.edits[0].file_path || args.edits[0].filePath || null;
      }
    } else if (tool === 'NotebookEdit') {
      filePath = args.notebook_path || args.notebookPath || null;
    }

    if (!filePath) {
      return;
    }

    // Normalize file path (remove project root if present)
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    filePath = filePath.replace(projectRoot, '').replace(/\\/g, '/');
    if (filePath.startsWith('/')) {
      filePath = filePath.substring(1);
    }

    // Determine repository
    const repository = getRepository(filePath);

    // Load existing logs
    let logs = loadEditLogs();

    // Add new edit
    logs.edits.push({
      timestamp: new Date().toISOString(),
      file: filePath,
      repository,
      tool
    });

    // Cleanup old logs
    logs = cleanupOldLogs(logs);

    // Save logs
    saveEditLogs(logs);

    // Optional: Log to console for debugging
    // console.log(`[post-tool-use] Logged edit: ${filePath} (${repository})`);
  } catch (error) {
    console.error('[post-tool-use] Hook error:', error);
  }
}

// Export for Claude Code hooks system
export default { run };
