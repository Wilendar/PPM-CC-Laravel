/**
 * SUBAGENT START HOOK
 *
 * Runs when a subagent STARTS execution
 * Logs agent initialization and injects context reminders
 *
 * Hook Event: SubagentStart (new in Claude Code 2.1.0)
 * Project: PPM-CC-Laravel
 * Created: 2026-01-08
 */

import * as fs from 'fs';
import * as path from 'path';

interface SubagentStartParams {
  agent_name: string;
  agent_type: string;
  session_id: string;
  cwd: string;
}

interface AgentStateFile {
  agent_name: string;
  started_at: string;
  session_id: string;
  status: 'running' | 'completed' | 'failed';
  task_description?: string;
}

/**
 * Initialize agent state tracking file
 */
function initializeAgentState(params: SubagentStartParams): void {
  try {
    const projectRoot = process.env.PROJECT_ROOT || params.cwd || process.cwd();
    const stateDir = path.join(projectRoot, '_AGENT_STATE');

    // Create state directory if it doesn't exist
    if (!fs.existsSync(stateDir)) {
      fs.mkdirSync(stateDir, { recursive: true });
    }

    const stateFile: AgentStateFile = {
      agent_name: params.agent_name,
      started_at: new Date().toISOString(),
      session_id: params.session_id,
      status: 'running'
    };

    const statePath = path.join(stateDir, `${params.agent_name}_current.json`);
    fs.writeFileSync(statePath, JSON.stringify(stateFile, null, 2), 'utf8');

  } catch (error) {
    // Silent fail - don't interrupt agent execution
    console.error('[subagent-start] Failed to initialize state:', error);
  }
}

/**
 * Generate context reminder based on agent type
 */
function getAgentReminder(agentName: string): string {
  const reminders: Record<string, string> = {
    'architect': 'ARCHITECT: Update Plan_Projektu/ with status icons. Add PLIK: paths for completed tasks.',
    'debugger': 'DEBUGGER: Diagnose first, confirm root cause with user before implementing fixes.',
    'deployment-specialist': 'DEPLOYMENT: Use REAL pscp/plink commands (pwsh wrapper). Verify with Claude in Chrome after deploy.',
    'livewire-specialist': 'LIVEWIRE: Check wire:key, dispatch(), nullable properties, wire:poll wrapper. Verify with Claude in Chrome.',
    'frontend-specialist': 'FRONTEND: NO inline styles, NO inline z-index. Use resources/css/ files. Run npm run build.',
    'laravel-expert': 'LARAVEL: Service layer architecture, max 300 lines per class, Context7 verification.',
    'prestashop-api-expert': 'PRESTASHOP: Factory pattern v8/v9, XML schema, readonly fields, manufacturer lookup.',
    'coding-style-agent': 'CODING-STYLE: PSR-12, Laravel conventions, max 300 lines, type hints, enterprise patterns.',
    'refactoring-specialist': 'REFACTORING: Max 300 lines per file. Use Traits for extraction. Preserve functionality.',
    'documentation-reader': 'DOCUMENTATION-READER: Read-only agent. Recommend specialist agents for code changes.',
    'ask': 'ASK: Answer questions only. Recommend specialist agents for implementation.'
  };

  return reminders[agentName] || `AGENT ${agentName.toUpperCase()}: Follow CLAUDE.md guidelines and project standards.`;
}

/**
 * Main hook function
 */
export async function run(hookParams: SubagentStartParams): Promise<{ message: string }> {
  try {
    const agentName = hookParams.agent_name || 'unknown';
    const agentType = hookParams.agent_type || 'general';

    // Initialize agent state tracking
    initializeAgentState(hookParams);

    // Generate context reminder
    const reminder = getAgentReminder(agentName);

    // Log to stderr (visible to user)
    console.error(`\n[SubagentStart] ${agentName} initialized`);
    console.error(`[Context] ${reminder}\n`);

    // Return message for stdout (visible to Claude)
    return {
      message: `SUBAGENT STARTED: ${agentName}\n\nCONTEXT REMINDER:\n${reminder}\n\nREMEMBER:\n- Use TodoWrite to track progress\n- Follow CLAUDE.md guidelines\n- Generate report in _AGENT_REPORTS/ when done\n- Check _ISSUES_FIXES/ for known problems`
    };

  } catch (error) {
    console.error('[subagent-start] Hook error:', error);
    return { message: '' };
  }
}

// Export for Claude Code hooks system
export default { run };
