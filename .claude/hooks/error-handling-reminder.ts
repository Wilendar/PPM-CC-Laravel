/**
 * ERROR HANDLING REMINDER HOOK
 *
 * Runs AFTER Claude finishes responding (Stop Event)
 * Detects risky patterns and reminds about error handling
 * Gentle, non-blocking reminder
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
 * Risky pattern definitions
 */
interface RiskyPattern {
  pattern: RegExp;
  description: string;
  category: 'backend' | 'database' | 'api' | 'async';
}

const RISKY_PATTERNS: RiskyPattern[] = [
  // Backend patterns
  {
    pattern: /try\s*{/i,
    description: 'Try-catch block found',
    category: 'backend'
  },
  {
    pattern: /extends\s+Controller/i,
    description: 'Controller class found',
    category: 'backend'
  },

  // Database patterns
  {
    pattern: /DB::/i,
    description: 'Direct DB query found',
    category: 'database'
  },
  {
    pattern: /Eloquent/i,
    description: 'Eloquent query found',
    category: 'database'
  },
  {
    pattern: /->save\(\)/i,
    description: 'Database save operation found',
    category: 'database'
  },
  {
    pattern: /->create\(/i,
    description: 'Database create operation found',
    category: 'database'
  },
  {
    pattern: /->update\(/i,
    description: 'Database update operation found',
    category: 'database'
  },
  {
    pattern: /->delete\(/i,
    description: 'Database delete operation found',
    category: 'database'
  },

  // API patterns
  {
    pattern: /Http::|Http\\Client/i,
    description: 'HTTP client usage found',
    category: 'api'
  },
  {
    pattern: /Guzzle/i,
    description: 'Guzzle HTTP client found',
    category: 'api'
  },
  {
    pattern: /curl_/i,
    description: 'cURL usage found',
    category: 'api'
  },
  {
    pattern: /PrestaShopClient/i,
    description: 'PrestaShop API client found',
    category: 'api'
  },

  // Async patterns
  {
    pattern: /Queue::|dispatch\(/i,
    description: 'Queue/Job dispatch found',
    category: 'async'
  },
  {
    pattern: /Bus::dispatch/i,
    description: 'Bus dispatch found',
    category: 'async'
  }
];

/**
 * Analyze file for risky patterns
 */
function analyzeFile(filePath: string): {
  hasRiskyPatterns: boolean;
  categories: Set<string>;
  patterns: string[];
} {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const fullPath = path.join(projectRoot, filePath);

    if (!fs.existsSync(fullPath)) {
      return { hasRiskyPatterns: false, categories: new Set(), patterns: [] };
    }

    const content = fs.readFileSync(fullPath, 'utf8');

    const categories = new Set<string>();
    const patterns: string[] = [];

    for (const riskyPattern of RISKY_PATTERNS) {
      if (riskyPattern.pattern.test(content)) {
        categories.add(riskyPattern.category);
        patterns.push(riskyPattern.description);
      }
    }

    return {
      hasRiskyPatterns: categories.size > 0,
      categories,
      patterns
    };
  } catch (error) {
    return { hasRiskyPatterns: false, categories: new Set(), patterns: [] };
  }
}

/**
 * Generate error handling reminder
 */
function generateReminder(
  backendFiles: string[],
  databaseFiles: string[],
  apiFiles: string[],
  asyncFiles: string[]
): string {
  if (
    backendFiles.length === 0 &&
    databaseFiles.length === 0 &&
    apiFiles.length === 0 &&
    asyncFiles.length === 0
  ) {
    return '';
  }

  let reminder = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
  reminder += '📋 ERROR HANDLING SELF-CHECK\n';
  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

  // Backend changes
  if (backendFiles.length > 0) {
    reminder += `⚠️  Backend Changes Detected\n`;
    reminder += `   ${backendFiles.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you add try-catch blocks where needed?\n`;
    reminder += `   ❓ Did you log errors? (Log::error())\n`;
    reminder += `   ❓ Did you return appropriate error responses?\n`;
    reminder += `\n   💡 Backend Best Practice:\n`;
    reminder += `      - All risky operations should have error handling\n`;
    reminder += `      - Always log errors with context\n`;
    reminder += `      - Return user-friendly error messages\n\n`;
  }

  // Database changes
  if (databaseFiles.length > 0) {
    reminder += `⚠️  Database Operations Detected\n`;
    reminder += `   ${databaseFiles.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you wrap database operations in try-catch?\n`;
    reminder += `   ❓ Did you use database transactions where appropriate?\n`;
    reminder += `   ❓ Did you handle constraint violations?\n`;
    reminder += `\n   💡 Database Best Practice:\n`;
    reminder += `      - Use transactions for multiple operations\n`;
    reminder += `      - Handle unique constraint violations gracefully\n`;
    reminder += `      - Log database errors with query context\n\n`;
  }

  // API changes
  if (apiFiles.length > 0) {
    reminder += `⚠️  API Calls Detected\n`;
    reminder += `   ${apiFiles.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you handle API failures gracefully?\n`;
    reminder += `   ❓ Did you implement timeout handling?\n`;
    reminder += `   ❓ Did you log API errors with request/response?\n`;
    reminder += `\n   💡 API Best Practice:\n`;
    reminder += `      - All API calls must have try-catch\n`;
    reminder += `      - Handle network timeouts and errors\n`;
    reminder += `      - Log full request/response for debugging\n`;
    reminder += `      - Consider retry logic for transient failures\n\n`;
  }

  // Async changes
  if (asyncFiles.length > 0) {
    reminder += `⚠️  Async Operations Detected\n`;
    reminder += `   ${asyncFiles.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you handle job failures?\n`;
    reminder += `   ❓ Did you implement failed() method in jobs?\n`;
    reminder += `   ❓ Did you set appropriate retry logic?\n`;
    reminder += `\n   💡 Async Best Practice:\n`;
    reminder += `      - All jobs should have failed() method\n`;
    reminder += `      - Set max retries and backoff\n`;
    reminder += `      - Log job failures with context\n\n`;
  }

  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

  return reminder;
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
      return {};
    }

    // Analyze files
    const backendFiles: string[] = [];
    const databaseFiles: string[] = [];
    const apiFiles: string[] = [];
    const asyncFiles: string[] = [];

    for (const file of editedFiles) {
      // Skip non-PHP files
      if (!file.endsWith('.php')) {
        continue;
      }

      const analysis = analyzeFile(file);

      if (!analysis.hasRiskyPatterns) {
        continue;
      }

      if (analysis.categories.has('backend')) {
        backendFiles.push(file);
      }
      if (analysis.categories.has('database')) {
        databaseFiles.push(file);
      }
      if (analysis.categories.has('api')) {
        apiFiles.push(file);
      }
      if (analysis.categories.has('async')) {
        asyncFiles.push(file);
      }
    }

    // Generate reminder
    const reminder = generateReminder(backendFiles, databaseFiles, apiFiles, asyncFiles);

    if (reminder) {
      return { message: reminder };
    }

    return {};
  } catch (error) {
    console.error('[error-handling-reminder] Hook error:', error);
    return {};
  }
}

// Export for Claude Code hooks system
export default { run };
