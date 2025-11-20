/**
 * USER PROMPT SUBMIT HOOK
 *
 * Runs BEFORE Claude sees the user's prompt
 * Analyzes prompt and injects Skill Activation reminders
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
  promptTriggers?: {
    keywords?: string[];
    intentPatterns?: string[];
  };
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
      console.error('[user-prompt-submit] skill-rules.json not found');
      return null;
    }

    const content = fs.readFileSync(skillRulesPath, 'utf8');
    return JSON.parse(content) as SkillRulesConfig;
  } catch (error) {
    console.error('[user-prompt-submit] Error loading skill-rules.json:', error);
    return null;
  }
}

/**
 * Load recent edit logs to check recently edited files
 */
function loadRecentEdits(): string[] {
  try {
    const projectRoot = process.env.PROJECT_ROOT || process.cwd();
    const editLogsPath = path.join(projectRoot, '.claude', 'edit-logs.json');

    if (!fs.existsSync(editLogsPath)) {
      return [];
    }

    const content = fs.readFileSync(editLogsPath, 'utf8');
    const logs: EditLog = JSON.parse(content);

    // Return files edited in last 10 minutes
    const tenMinutesAgo = Date.now() - (10 * 60 * 1000);
    return logs.edits
      .filter(edit => new Date(edit.timestamp).getTime() > tenMinutesAgo)
      .map(edit => edit.file);
  } catch (error) {
    return [];
  }
}

/**
 * Check if prompt matches skill keywords
 */
function matchesKeywords(prompt: string, keywords: string[]): boolean {
  const lowerPrompt = prompt.toLowerCase();
  return keywords.some(keyword => lowerPrompt.includes(keyword.toLowerCase()));
}

/**
 * Check if prompt matches intent patterns
 */
function matchesIntentPatterns(prompt: string, patterns: string[]): boolean {
  return patterns.some(pattern => {
    try {
      const regex = new RegExp(pattern, 'i');
      return regex.test(prompt);
    } catch (error) {
      return false;
    }
  });
}

/**
 * Check if recently edited files match skill file patterns
 */
function matchesFilePatterns(recentFiles: string[], patterns: string[]): boolean {
  return recentFiles.some(file => {
    return patterns.some(pattern => {
      // Convert glob pattern to regex
      const regexPattern = pattern
        .replace(/\*\*/g, '.*')
        .replace(/\*/g, '[^/]*')
        .replace(/\./g, '\\.');

      try {
        const regex = new RegExp(regexPattern, 'i');
        return regex.test(file);
      } catch (error) {
        return false;
      }
    });
  });
}

/**
 * Analyze prompt and determine relevant skills
 */
function analyzePrompt(prompt: string, recentFiles: string[], config: SkillRulesConfig): Array<{
  skillName: string;
  rule: SkillRule;
  matchReason: string[];
}> {
  const matches: Array<{
    skillName: string;
    rule: SkillRule;
    matchReason: string[];
  }> = [];

  for (const [skillName, rule] of Object.entries(config.skills)) {
    const reasons: string[] = [];

    // Check prompt triggers
    if (rule.promptTriggers) {
      if (rule.promptTriggers.keywords && matchesKeywords(prompt, rule.promptTriggers.keywords)) {
        reasons.push('Keyword match');
      }

      if (rule.promptTriggers.intentPatterns && matchesIntentPatterns(prompt, rule.promptTriggers.intentPatterns)) {
        reasons.push('Intent pattern match');
      }
    }

    // Check file triggers
    if (rule.fileTriggers && recentFiles.length > 0) {
      if (rule.fileTriggers.pathPatterns && matchesFilePatterns(recentFiles, rule.fileTriggers.pathPatterns)) {
        reasons.push('Recently edited related files');
      }
    }

    if (reasons.length > 0) {
      matches.push({ skillName, rule, matchReason: reasons });
    }
  }

  // Sort by priority
  const priorityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
  matches.sort((a, b) => priorityOrder[b.rule.priority] - priorityOrder[a.rule.priority]);

  return matches;
}

/**
 * Generate skill activation reminder
 */
function generateReminder(matches: Array<{
  skillName: string;
  rule: SkillRule;
  matchReason: string[];
}>): string {
  if (matches.length === 0) {
    return '';
  }

  let reminder = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
  reminder += '🎯 SKILL ACTIVATION CHECK\n';
  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';
  reminder += 'Based on your request, consider using:\n\n';

  for (const match of matches) {
    const emoji = match.rule.priority === 'critical' || match.rule.enforcement === 'require' ? '🔴' : '🟡';
    const level = match.rule.priority === 'critical' || match.rule.enforcement === 'require' ? 'CRITICAL' : 'SUGGESTED';

    reminder += `${emoji} ${level}: ${match.skillName}\n`;
    reminder += `   → ${match.rule.description}\n`;

    if (match.rule.criticalRules && match.rule.criticalRules.length > 0) {
      reminder += `   → Remember:\n`;
      for (const criticalRule of match.rule.criticalRules) {
        reminder += `     • ${criticalRule}\n`;
      }
    }

    reminder += '\n';
  }

  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

  return reminder;
}

/**
 * Main hook function
 */
export async function run(hookParams: {
  userPrompt: string;
  context: any;
}): Promise<{ userPrompt: string }> {
  try {
    // Load configuration
    const config = loadSkillRules();
    if (!config) {
      return { userPrompt: hookParams.userPrompt };
    }

    // Load recent edits
    const recentFiles = loadRecentEdits();

    // Analyze prompt
    const matches = analyzePrompt(hookParams.userPrompt, recentFiles, config);

    // Generate reminder
    const reminder = generateReminder(matches);

    // Inject reminder into prompt
    if (reminder) {
      return {
        userPrompt: reminder + '\n\n' + hookParams.userPrompt
      };
    }

    return { userPrompt: hookParams.userPrompt };
  } catch (error) {
    console.error('[user-prompt-submit] Hook error:', error);
    return { userPrompt: hookParams.userPrompt };
  }
}

// Export for Claude Code hooks system
export default { run };
