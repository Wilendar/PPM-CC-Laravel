/**
 * STOP EVENT HOOK
 *
 * Runs AFTER Claude finishes responding
 * Analyzes edited files and shows skill reminders + self-checks
 *
 * Based on: Reddit "Claude Code is a Beast" guide
 * Project: PPM-CC-Laravel-TEST
 * Created: 2025-11-04
 */

import * as fs from 'fs';
import * as path from 'path';

interface SkillRule {
  type: string;
  enforcement: 'suggest' | 'require' | 'block';
  priority: 'low' | 'medium' | 'high' | 'critical';
  fileTriggers?: {
    pathPatterns?: string[];
    contentPatterns?: string[];
  };
  criticalRules?: string[];
  description: string;
}

interface SkillRulesConfig {
  skills: Record<string, SkillRule>;
}

interface EditLog {
  edits: Array<{
    timestamp: string;
    file: string;
    repository: string;
    tool: string;
  }>;
}

/**
 * Load skill-rules.json configuration
 */
function loadSkillRules(): SkillRulesConfig | null {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const skillRulesPath = path.join(projectRoot, '.claude', 'skill-rules.json');

    if (!fs.existsSync(skillRulesPath)) {
      console.error('[stop-event] skill-rules.json not found');
      return null;
    }

    const content = fs.readFileSync(skillRulesPath, 'utf8');
    return JSON.parse(content) as SkillRulesConfig;
  } catch (error) {
    console.error('[stop-event] Error loading skill-rules.json:', error);
    return null;
  }
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
 * Check if file path matches pattern
 */
function matchesPathPattern(filePath: string, pattern: string): boolean {
  const regexPattern = pattern
    .replace(/\*\*/g, '.*')
    .replace(/\*/g, '[^/]*')
    .replace(/\./g, '\\.');

  try {
    const regex = new RegExp(regexPattern, 'i');
    return regex.test(filePath);
  } catch (error) {
    return false;
  }
}

/**
 * Check file content for patterns
 */
function checkFileContent(filePath: string, patterns: string[]): boolean {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const fullPath = path.join(projectRoot, filePath);

    if (!fs.existsSync(fullPath)) {
      return false;
    }

    const content = fs.readFileSync(fullPath, 'utf8');

    return patterns.some(pattern => {
      try {
        const regex = new RegExp(pattern, 'i');
        return regex.test(content);
      } catch (error) {
        return false;
      }
    });
  } catch (error) {
    return false;
  }
}

/**
 * Detect risky patterns in edited files
 */
interface RiskyPattern {
  pattern: string;
  description: string;
  checkQuestion: string;
}

const RISKY_PATTERNS: RiskyPattern[] = [
  {
    pattern: 'style="',
    description: 'Inline styles detected',
    checkQuestion: 'Did you avoid inline styles? (style="..." ❌)'
  },
  {
    pattern: 'class="[^"]*z-\\[',
    description: 'Arbitrary Tailwind values detected',
    checkQuestion: 'Did you avoid arbitrary Tailwind? (z-[9999] ❌)'
  },
  {
    pattern: 'Product::find\\(',
    description: 'Hard-coded ID usage detected',
    checkQuestion: 'Did you avoid Product::find($id)? ❌'
  },
  {
    pattern: '->find\\(\\$',
    description: 'Hard-coded ID lookup detected',
    checkQuestion: 'Did you use SKU instead of ID? ✅'
  }
];

function detectRiskyPatterns(filePath: string): RiskyPattern[] {
  const detected: RiskyPattern[] = [];

  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const fullPath = path.join(projectRoot, filePath);

    if (!fs.existsSync(fullPath)) {
      return detected;
    }

    const content = fs.readFileSync(fullPath, 'utf8');

    for (const riskyPattern of RISKY_PATTERNS) {
      try {
        const regex = new RegExp(riskyPattern.pattern, 'i');
        if (regex.test(content)) {
          detected.push(riskyPattern);
        }
      } catch (error) {
        // Ignore regex errors
      }
    }
  } catch (error) {
    // Ignore file read errors
  }

  return detected;
}

/**
 * Analyze edited files and find relevant skills
 */
function analyzeEditedFiles(editedFiles: string[], config: SkillRulesConfig): Array<{
  skillName: string;
  rule: SkillRule;
  matchingFiles: string[];
}> {
  const matches: Array<{
    skillName: string;
    rule: SkillRule;
    matchingFiles: string[];
  }> = [];

  for (const [skillName, rule] of Object.entries(config.skills)) {
    if (!rule.fileTriggers) {
      continue;
    }

    const matchingFiles: string[] = [];

    for (const file of editedFiles) {
      // Check path patterns
      if (rule.fileTriggers.pathPatterns) {
        const pathMatch = rule.fileTriggers.pathPatterns.some(pattern =>
          matchesPathPattern(file, pattern)
        );

        if (pathMatch) {
          // Check content patterns if specified
          if (rule.fileTriggers.contentPatterns) {
            if (checkFileContent(file, rule.fileTriggers.contentPatterns)) {
              matchingFiles.push(file);
            }
          } else {
            matchingFiles.push(file);
          }
        }
      }
    }

    if (matchingFiles.length > 0) {
      matches.push({ skillName, rule, matchingFiles });
    }
  }

  // Sort by priority
  const priorityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
  matches.sort((a, b) => priorityOrder[b.rule.priority] - priorityOrder[a.rule.priority]);

  return matches;
}

/**
 * Generate self-check reminder
 */
function generateSelfCheckReminder(
  matches: Array<{
    skillName: string;
    rule: SkillRule;
    matchingFiles: string[];
  }>,
  editedFiles: string[]
): string {
  if (matches.length === 0) {
    return '';
  }

  let reminder = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
  reminder += '📋 SELF-CHECK REMINDER\n';
  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

  // Group by category
  const frontendMatches = matches.filter(m => m.skillName.includes('frontend'));
  const livewireMatches = matches.filter(m => m.skillName.includes('livewire'));
  const skuMatches = matches.filter(m => m.skillName.includes('sku'));
  const prestashopMatches = matches.filter(m => m.skillName.includes('prestashop'));

  // Frontend changes
  if (frontendMatches.length > 0) {
    const files = frontendMatches.flatMap(m => m.matchingFiles);
    reminder += `⚠️  Frontend Changes Detected\n`;
    reminder += `   ${files.length} file(s) edited\n\n`;

    // Check for risky patterns
    const riskyPatterns = new Set<string>();
    for (const file of files) {
      const patterns = detectRiskyPatterns(file);
      patterns.forEach(p => riskyPatterns.add(p.checkQuestion));
    }

    if (riskyPatterns.size > 0) {
      for (const question of riskyPatterns) {
        reminder += `   ❓ ${question}\n`;
      }
    } else {
      reminder += `   ❓ Did you avoid inline styles? (style="..." ❌)\n`;
      reminder += `   ❓ Did you avoid arbitrary Tailwind? (z-[9999] ❌)\n`;
      reminder += `   ❓ Did you use dedicated CSS classes? (.modal-overlay ✅)\n`;
    }

    reminder += `\n   💡 Frontend Rule:\n`;
    reminder += `      - All styles must be in dedicated CSS files\n`;
    reminder += `      - Reference: resources/css/components/*.css\n`;
    reminder += `      - MANDATORY: Screenshot verification required\n\n`;
  }

  // Livewire changes
  if (livewireMatches.length > 0) {
    const files = livewireMatches.flatMap(m => m.matchingFiles);
    reminder += `⚠️  Livewire Changes Detected\n`;
    reminder += `   ${files.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you use Single Responsibility pattern?\n`;
    reminder += `   ❓ Did you extract traits for large components?\n`;
    reminder += `   ❓ Did you inject services instead of inline logic?\n`;
    reminder += `\n   💡 Livewire Best Practice:\n`;
    reminder += `      - Max 300 lines per component\n`;
    reminder += `      - Use trait composition (like ProductForm)\n`;
    reminder += `      - Service injection for business logic\n\n`;
  }

  // SKU architecture changes
  if (skuMatches.length > 0) {
    const files = skuMatches.flatMap(m => m.matchingFiles);
    reminder += `⚠️  Product Model Changes Detected\n`;
    reminder += `   ${files.length} file(s) edited\n\n`;

    // Check for risky patterns
    const skuRiskyPatterns = new Set<string>();
    for (const file of files) {
      const patterns = detectRiskyPatterns(file);
      patterns.filter(p => p.pattern.includes('find')).forEach(p => skuRiskyPatterns.add(p.checkQuestion));
    }

    if (skuRiskyPatterns.size > 0) {
      for (const question of skuRiskyPatterns) {
        reminder += `   ❓ ${question}\n`;
      }
    } else {
      reminder += `   ❓ Did you use SKU instead of ID?\n`;
      reminder += `   ❓ Did you avoid Product::find($id)? ❌\n`;
      reminder += `   ❓ Did you use Product::where('sku', $sku)? ✅\n`;
    }

    reminder += `\n   💡 SKU-First Rule:\n`;
    reminder += `      - ALWAYS use SKU as primary identifier\n`;
    reminder += `      - Reference: _DOCS/SKU_ARCHITECTURE_GUIDE.md\n\n`;
  }

  // PrestaShop changes
  if (prestashopMatches.length > 0) {
    const files = prestashopMatches.flatMap(m => m.matchingFiles);
    reminder += `⚠️  PrestaShop Integration Changes Detected\n`;
    reminder += `   ${files.length} file(s) edited\n\n`;
    reminder += `   ❓ Did you verify mappings (category, price, warehouse)?\n`;
    reminder += `   ❓ Did you handle sync conflicts?\n`;
    reminder += `   ❓ Did you update sync logs?\n`;
    reminder += `\n   💡 PrestaShop Best Practice:\n`;
    reminder += `      - Always verify mappings before sync\n`;
    reminder += `      - Handle conflicts gracefully\n`;
    reminder += `      - Log all sync operations\n\n`;
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
    // Load configuration
    const config = loadSkillRules();
    if (!config) {
      return {};
    }

    // Load recent edits
    const logs = loadRecentEdits();
    const editedFiles = getCurrentSessionEdits(logs);

    if (editedFiles.length === 0) {
      return {};
    }

    // Analyze edited files
    const matches = analyzeEditedFiles(editedFiles, config);

    // Generate self-check reminder
    const reminder = generateSelfCheckReminder(matches, editedFiles);

    if (reminder) {
      return { message: reminder };
    }

    return {};
  } catch (error) {
    console.error('[stop-event] Hook error:', error);
    return {};
  }
}

// Export for Claude Code hooks system
export default { run };
