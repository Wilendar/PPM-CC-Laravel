/**
 * BUILD CHECKER HOOK
 *
 * Runs AFTER Claude finishes responding (Stop Event)
 * Checks edited repositories and runs build scripts
 * Catches TypeScript/PHP errors immediately
 *
 * Based on: Reddit "Claude Code is a Beast" guide
 * Project: PPM-CC-Laravel-TEST
 * Created: 2025-11-04
 */

import * as fs from 'fs';
import * as path from 'path';
import { execSync } from 'child_process';

interface EditLog {
  edits: Array<{
    timestamp: string;
    file: string;
    repository: string;
    tool: string;
  }>;
}

interface BuildLog {
  timestamp: string;
  repository: string;
  command: string;
  success: boolean;
  errorCount: number;
  errors: string[];
}

interface BuildLogsFile {
  builds: BuildLog[];
}

/**
 * Load recent edit logs
 */
function loadRecentEdits(): EditLog {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const editLogsPath = path.join(projectRoot, '.claude', 'edit-logs.json');

    if (!fs.existsSync(editLogsPath)) {
      return { edits: [] };
    }

    const content = fs.readFileSync(editLogsPath, 'utf8');
    return JSON.parse(content) as EditLog;
  } catch (error) {
    return { edits: [] };
  }
}

/**
 * Get files edited in current session (last 5 minutes)
 */
function getCurrentSessionEdits(logs: EditLog): string[] {
  const fiveMinutesAgo = Date.now() - (5 * 60 * 1000);
  return logs.edits
    .filter(edit => new Date(edit.timestamp).getTime() > fiveMinutesAgo)
    .map(edit => edit.file);
}

/**
 * Get affected repositories from edited files
 */
function getAffectedRepositories(editedFiles: string[]): Set<string> {
  const repos = new Set<string>();

  for (const file of editedFiles) {
    if (file.includes('/app/') || file.includes('/database/') || file.includes('/routes/')) {
      repos.add('main');
    } else if (file.includes('/resources/')) {
      repos.add('frontend');
    }
  }

  return repos;
}

/**
 * Run build command and capture output
 */
function runBuild(command: string, cwd: string): { success: boolean; output: string } {
  try {
    const output = execSync(command, {
      cwd,
      encoding: 'utf8',
      stdio: 'pipe',
      timeout: 120000 // 2 minutes timeout
    });

    return { success: true, output };
  } catch (error: any) {
    // Build failed
    const output = error.stdout || error.stderr || error.message;
    return { success: false, output };
  }
}

/**
 * Parse PHPStan errors
 */
function parsePHPStanErrors(output: string): string[] {
  const errors: string[] = [];
  const lines = output.split('\n');

  for (const line of lines) {
    // PHPStan error format: "  ------ ---------------------------- file.php"
    // Or: "  Line   Error   file.php:123"
    if (line.trim() && !line.includes('-----') && !line.includes('Line') && line.includes('.php')) {
      errors.push(line.trim());
    }
  }

  return errors;
}

/**
 * Parse Vite errors
 */
function parseViteErrors(output: string): string[] {
  const errors: string[] = [];
  const lines = output.split('\n');

  for (const line of lines) {
    // Vite error format typically includes "error" keyword
    if (line.toLowerCase().includes('error') && !line.includes('0 errors')) {
      errors.push(line.trim());
    }
  }

  return errors;
}

/**
 * Save build logs
 */
function saveBuildLogs(log: BuildLog): void {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const buildLogsPath = path.join(projectRoot, '.claude', 'build-logs.json');

    // Load existing logs
    let logs: BuildLogsFile = { builds: [] };
    if (fs.existsSync(buildLogsPath)) {
      const content = fs.readFileSync(buildLogsPath, 'utf8');
      logs = JSON.parse(content) as BuildLogsFile;
    }

    // Add new log
    logs.builds.push(log);

    // Keep last 50 builds
    if (logs.builds.length > 50) {
      logs.builds = logs.builds.slice(-50);
    }

    // Save
    fs.writeFileSync(buildLogsPath, JSON.stringify(logs, null, 2), 'utf8');
  } catch (error) {
    console.error('[build-checker] Error saving build logs:', error);
  }
}

/**
 * Check main repository (PHP/Laravel)
 */
function checkMainRepository(): BuildLog | null {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();

    console.log('[build-checker] Running PHPStan...');

    const { success, output } = runBuild('composer phpstan', projectRoot);

    const errors = parsePHPStanErrors(output);

    const log: BuildLog = {
      timestamp: new Date().toISOString(),
      repository: 'main',
      command: 'composer phpstan',
      success,
      errorCount: errors.length,
      errors: errors.slice(0, 10) // Keep first 10 errors
    };

    saveBuildLogs(log);

    return log;
  } catch (error) {
    console.error('[build-checker] Error checking main repository:', error);
    return null;
  }
}

/**
 * Check frontend repository (Vite)
 */
function checkFrontendRepository(): BuildLog | null {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();

    console.log('[build-checker] Running Vite build...');

    const { success, output } = runBuild('npm run build', projectRoot);

    const errors = parseViteErrors(output);

    const log: BuildLog = {
      timestamp: new Date().toISOString(),
      repository: 'frontend',
      command: 'npm run build',
      success,
      errorCount: errors.length,
      errors: errors.slice(0, 10) // Keep first 10 errors
    };

    saveBuildLogs(log);

    return log;
  } catch (error) {
    console.error('[build-checker] Error checking frontend repository:', error);
    return null;
  }
}

/**
 * Generate build report
 */
function generateBuildReport(logs: BuildLog[]): string {
  if (logs.length === 0) {
    return '';
  }

  let report = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
  report += '🔨 BUILD CHECK RESULTS\n';
  report += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

  for (const log of logs) {
    const emoji = log.success ? '✅' : '❌';
    report += `${emoji} Repository: ${log.repository}\n`;
    report += `   Command: ${log.command}\n`;

    if (log.success) {
      report += `   Status: Build successful! No errors found.\n`;
    } else {
      report += `   Status: Build failed with ${log.errorCount} error(s)\n\n`;

      if (log.errorCount < 5) {
        // Show errors directly
        report += `   Errors:\n`;
        for (const error of log.errors) {
          report += `   - ${error}\n`;
        }
        report += `\n   Please fix these errors before continuing.\n`;
      } else {
        // Too many errors, suggest agent
        report += `   ⚠️  Too many errors (${log.errorCount})\n`;
        report += `   💡 Consider launching build-error-resolver agent\n`;
        report += `\n   First 5 errors:\n`;
        for (const error of log.errors.slice(0, 5)) {
          report += `   - ${error}\n`;
        }
      }
    }

    report += '\n';
  }

  report += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

  return report;
}

/**
 * Main hook function
 */
export async function run(hookParams: {
  response: string;
  context: any;
}): Promise<{ message?: string }> {
  try {
    // Load recent edits
    const logs = loadRecentEdits();
    const editedFiles = getCurrentSessionEdits(logs);

    if (editedFiles.length === 0) {
      // No files edited, skip build check
      return {};
    }

    // Get affected repositories
    const affectedRepos = getAffectedRepositories(editedFiles);

    if (affectedRepos.size === 0) {
      // No relevant repositories affected
      return {};
    }

    console.log(`[build-checker] Affected repositories: ${Array.from(affectedRepos).join(', ')}`);

    // Run builds
    const buildLogs: BuildLog[] = [];

    if (affectedRepos.has('main')) {
      const log = checkMainRepository();
      if (log) {
        buildLogs.push(log);
      }
    }

    if (affectedRepos.has('frontend')) {
      const log = checkFrontendRepository();
      if (log) {
        buildLogs.push(log);
      }
    }

    // Generate report
    const report = generateBuildReport(buildLogs);

    if (report) {
      return { message: report };
    }

    return {};
  } catch (error) {
    console.error('[build-checker] Hook error:', error);
    return {};
  }
}

// Export for Claude Code hooks system
export default { run };
