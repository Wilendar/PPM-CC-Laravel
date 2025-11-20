/**
 * SUBAGENT REPORT GENERATOR HOOK
 *
 * Runs AFTER subagent finishes execution (SubagentStop event)
 * Automatically generates standardized agent reports
 *
 * Uses NEW fields: agent_id, agent_transcript_path
 * Project: PPM-CC-Laravel
 * Created: 2025-11-17
 */

import * as fs from 'fs';
import * as path from 'path';

interface SubagentStopParams {
  session_id: string;
  transcript_path: string;
  agent_id: string;
  agent_transcript_path: string;
  hook_event_name: 'SubagentStop';
  cwd: string;
  permission_mode: string;
  stop_hook_active: boolean;
}

interface TranscriptMessage {
  type: 'message' | 'tool_use' | 'tool_result';
  role?: 'user' | 'assistant';
  content?: string | any[];
  name?: string;
  args?: any;
}

interface FileOperation {
  tool: string;
  file: string;
  timestamp: string;
  description?: string;
}

interface AgentAnalysis {
  agentType: string;
  taskDescription: string;
  filesModified: FileOperation[];
  errors: string[];
  warnings: string[];
  nextSteps: string[];
  duration: number;
  toolsUsed: string[];
}

/**
 * Load and parse JSONL transcript
 */
function loadTranscript(transcriptPath: string): TranscriptMessage[] {
  try {
    if (!fs.existsSync(transcriptPath)) {
      console.error(`[subagent-report-generator] Transcript not found: ${transcriptPath}`);
      return [];
    }

    const content = fs.readFileSync(transcriptPath, 'utf8');
    const lines = content.trim().split('\n');

    const messages: TranscriptMessage[] = [];
    for (const line of lines) {
      if (!line.trim()) continue;

      try {
        const msg = JSON.parse(line);
        messages.push(msg);
      } catch (error) {
        console.error('[subagent-report-generator] Failed to parse line:', error);
      }
    }

    return messages;
  } catch (error) {
    console.error('[subagent-report-generator] Error loading transcript:', error);
    return [];
  }
}

/**
 * Extract agent type from initial prompt or subagent type
 */
function extractAgentType(messages: TranscriptMessage[]): string {
  // Try to find agent type from first user message
  const firstUserMsg = messages.find(m => m.type === 'message' && m.role === 'user');

  if (firstUserMsg && typeof firstUserMsg.content === 'string') {
    const content = firstUserMsg.content.toLowerCase();

    // Check for agent type indicators
    const agentTypes = [
      'architect',
      'debugger',
      'deployment-specialist',
      'documentation-reader',
      'erp-integration-expert',
      'frontend-specialist',
      'import-export-specialist',
      'laravel-expert',
      'livewire-specialist',
      'prestashop-api-expert',
      'refactoring-specialist',
      'coding-style-agent'
    ];

    for (const type of agentTypes) {
      if (content.includes(type.replace('-', ' ')) || content.includes(type)) {
        return type;
      }
    }
  }

  return 'general-purpose';
}

/**
 * Extract task description from initial prompt
 */
function extractTaskDescription(messages: TranscriptMessage[]): string {
  const firstUserMsg = messages.find(m => m.type === 'message' && m.role === 'user');

  if (firstUserMsg && typeof firstUserMsg.content === 'string') {
    // Take first 200 characters or first paragraph
    const content = firstUserMsg.content.trim();
    const firstLine = content.split('\n')[0];
    return firstLine.length > 200 ? firstLine.substring(0, 200) + '...' : firstLine;
  }

  return 'No task description available';
}

/**
 * Extract file operations from transcript
 */
function extractFileOperations(messages: TranscriptMessage[]): FileOperation[] {
  const operations: FileOperation[] = [];
  const editTools = ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'];

  for (const msg of messages) {
    if (msg.type !== 'tool_use' || !msg.name || !editTools.includes(msg.name)) {
      continue;
    }

    let filePath: string | null = null;

    if (msg.name === 'Edit' || msg.name === 'Write') {
      filePath = msg.args?.file_path || msg.args?.filePath || null;
    } else if (msg.name === 'MultiEdit' && msg.args?.edits) {
      // Handle multiple files
      for (const edit of msg.args.edits) {
        const multiFilePath = edit.file_path || edit.filePath;
        if (multiFilePath) {
          operations.push({
            tool: msg.name,
            file: normalizeFilePath(multiFilePath),
            timestamp: new Date().toISOString(),
            description: extractEditDescription(edit)
          });
        }
      }
      continue;
    } else if (msg.name === 'NotebookEdit') {
      filePath = msg.args?.notebook_path || msg.args?.notebookPath || null;
    }

    if (filePath) {
      operations.push({
        tool: msg.name,
        file: normalizeFilePath(filePath),
        timestamp: new Date().toISOString(),
        description: extractEditDescription(msg.args)
      });
    }
  }

  return operations;
}

/**
 * Extract edit description from args
 */
function extractEditDescription(args: any): string {
  if (!args) return '';

  if (args.new_string) {
    const lines = args.new_string.split('\n').length;
    return `Modified ${lines} line(s)`;
  }

  if (args.content) {
    const lines = args.content.split('\n').length;
    return `Created/Updated ${lines} line(s)`;
  }

  return '';
}

/**
 * Normalize file path
 */
function normalizeFilePath(filePath: string): string {
  return filePath.replace(/\\/g, '/').replace(/^\/+/, '');
}

/**
 * Extract errors from transcript
 */
function extractErrors(messages: TranscriptMessage[]): string[] {
  const errors: string[] = [];

  for (const msg of messages) {
    if (msg.type === 'tool_result' && typeof msg.content === 'string') {
      // Check for error indicators
      if (msg.content.toLowerCase().includes('error') ||
          msg.content.toLowerCase().includes('failed') ||
          msg.content.toLowerCase().includes('exception')) {
        const errorLines = msg.content.split('\n').filter(line =>
          line.toLowerCase().includes('error') ||
          line.toLowerCase().includes('failed')
        );
        errors.push(...errorLines.slice(0, 3)); // Max 3 error lines per result
      }
    }

    if (msg.type === 'message' && msg.role === 'assistant' && typeof msg.content === 'string') {
      // Check for error mentions in assistant messages
      const content = msg.content.toLowerCase();
      if (content.includes('błąd') || content.includes('problem') || content.includes('niepowodzenie')) {
        const lines = msg.content.split('\n');
        const problemLines = lines.filter(line =>
          line.toLowerCase().includes('błąd') ||
          line.toLowerCase().includes('problem')
        );
        errors.push(...problemLines.slice(0, 2));
      }
    }
  }

  return errors;
}

/**
 * Extract warnings from transcript
 */
function extractWarnings(messages: TranscriptMessage[]): string[] {
  const warnings: string[] = [];

  for (const msg of messages) {
    if (msg.type === 'message' && msg.role === 'assistant' && typeof msg.content === 'string') {
      const content = msg.content.toLowerCase();
      if (content.includes('uwaga') || content.includes('warning') || content.includes('ostrzeżenie')) {
        const lines = msg.content.split('\n');
        const warningLines = lines.filter(line =>
          line.toLowerCase().includes('uwaga') ||
          line.toLowerCase().includes('warning')
        );
        warnings.push(...warningLines.slice(0, 3));
      }
    }
  }

  return warnings;
}

/**
 * Extract next steps from final assistant message
 */
function extractNextSteps(messages: TranscriptMessage[]): string[] {
  const steps: string[] = [];

  // Find last assistant message
  const assistantMessages = messages.filter(m => m.type === 'message' && m.role === 'assistant');
  const lastMessage = assistantMessages[assistantMessages.length - 1];

  if (lastMessage && typeof lastMessage.content === 'string') {
    const content = lastMessage.content;

    // Look for "next steps", "następne kroki", "TODO", etc.
    const patterns = [
      /następne kroki:?\s*([\s\S]*?)(?=\n\n|$)/i,
      /next steps:?\s*([\s\S]*?)(?=\n\n|$)/i,
      /todo:?\s*([\s\S]*?)(?=\n\n|$)/i,
      /do zrobienia:?\s*([\s\S]*?)(?=\n\n|$)/i
    ];

    for (const pattern of patterns) {
      const match = content.match(pattern);
      if (match && match[1]) {
        const lines = match[1].trim().split('\n');
        steps.push(...lines.filter(line => line.trim()).slice(0, 5));
        break;
      }
    }
  }

  return steps;
}

/**
 * Extract tools used
 */
function extractToolsUsed(messages: TranscriptMessage[]): string[] {
  const tools = new Set<string>();

  for (const msg of messages) {
    if (msg.type === 'tool_use' && msg.name) {
      tools.add(msg.name);
    }
  }

  return Array.from(tools).sort();
}

/**
 * Calculate execution duration
 */
function calculateDuration(messages: TranscriptMessage[]): number {
  if (messages.length === 0) return 0;

  // This is a simplified calculation - in reality we'd need timestamps from the transcript
  // For now, estimate based on number of messages and tools
  const toolUses = messages.filter(m => m.type === 'tool_use').length;
  const estimatedSeconds = toolUses * 5; // Rough estimate: 5 seconds per tool use

  return estimatedSeconds;
}

/**
 * Analyze transcript
 */
function analyzeTranscript(messages: TranscriptMessage[]): AgentAnalysis {
  return {
    agentType: extractAgentType(messages),
    taskDescription: extractTaskDescription(messages),
    filesModified: extractFileOperations(messages),
    errors: extractErrors(messages),
    warnings: extractWarnings(messages),
    nextSteps: extractNextSteps(messages),
    duration: calculateDuration(messages),
    toolsUsed: extractToolsUsed(messages)
  };
}

/**
 * Generate markdown report
 */
function generateReport(agentId: string, analysis: AgentAnalysis): string {
  const timestamp = new Date().toISOString().replace('T', ' ').substring(0, 16);

  let report = `# RAPORT PRACY AGENTA: ${analysis.agentType}\n\n`;
  report += `**Data**: ${timestamp}\n`;
  report += `**Agent ID**: ${agentId}\n`;
  report += `**Agent Type**: ${analysis.agentType}\n`;
  report += `**Zadanie**: ${analysis.taskDescription}\n`;
  report += `**Czas wykonania**: ~${Math.floor(analysis.duration / 60)} min ${analysis.duration % 60} sek\n\n`;
  report += `---\n\n`;

  // Wykonane prace
  report += `## ✅ WYKONANE PRACE\n\n`;

  if (analysis.filesModified.length > 0) {
    report += `**Zmodyfikowane pliki (${analysis.filesModified.length})**:\n\n`;

    // Group by tool type
    const byTool: Record<string, FileOperation[]> = {};
    for (const op of analysis.filesModified) {
      if (!byTool[op.tool]) byTool[op.tool] = [];
      byTool[op.tool].push(op);
    }

    for (const [tool, ops] of Object.entries(byTool)) {
      report += `### ${tool} (${ops.length} operacji)\n\n`;
      for (const op of ops) {
        report += `- \`${op.file}\``;
        if (op.description) {
          report += ` - ${op.description}`;
        }
        report += '\n';
      }
      report += '\n';
    }
  } else {
    report += `- Brak zmodyfikowanych plików (agent wykonał zadanie bez edycji kodu)\n\n`;
  }

  if (analysis.toolsUsed.length > 0) {
    report += `**Użyte narzędzia**: ${analysis.toolsUsed.join(', ')}\n\n`;
  }

  // Problemy/Blokery
  report += `## ⚠️ PROBLEMY/BLOKERY\n\n`;

  if (analysis.errors.length > 0) {
    report += `**Napotkane błędy**:\n\n`;
    for (const error of analysis.errors.slice(0, 5)) {
      report += `- ${error.trim()}\n`;
    }
    if (analysis.errors.length > 5) {
      report += `- ... i ${analysis.errors.length - 5} innych błędów\n`;
    }
    report += '\n';
  }

  if (analysis.warnings.length > 0) {
    report += `**Ostrzeżenia**:\n\n`;
    for (const warning of analysis.warnings.slice(0, 5)) {
      report += `- ${warning.trim()}\n`;
    }
    report += '\n';
  }

  if (analysis.errors.length === 0 && analysis.warnings.length === 0) {
    report += `- Brak napotkanych problemów lub blokerów\n\n`;
  }

  // Następne kroki
  report += `## 📋 NASTĘPNE KROKI\n\n`;

  if (analysis.nextSteps.length > 0) {
    for (const step of analysis.nextSteps) {
      report += `- ${step.trim()}\n`;
    }
    report += '\n';
  } else {
    report += `- Agent ukończył zadanie - brak zdefiniowanych następnych kroków\n\n`;
  }

  // Pliki (szczegółowa lista)
  report += `## 📁 PLIKI\n\n`;

  if (analysis.filesModified.length > 0) {
    for (const op of analysis.filesModified) {
      report += `- **${op.file}** (${op.tool})`;
      if (op.description) {
        report += ` - ${op.description}`;
      }
      report += '\n';
    }
  } else {
    report += `- Brak plików do raportowania\n`;
  }

  report += '\n---\n\n';
  report += `*Raport wygenerowany automatycznie przez subagent-report-generator hook*\n`;

  return report;
}

/**
 * Save report to _AGENT_REPORTS
 */
function saveReport(projectRoot: string, agentId: string, agentType: string, report: string): string | null {
  try {
    const reportsDir = path.join(projectRoot, '_AGENT_REPORTS');

    // Ensure directory exists
    if (!fs.existsSync(reportsDir)) {
      fs.mkdirSync(reportsDir, { recursive: true });
    }

    // Generate filename with timestamp
    const timestamp = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
    const sanitizedAgentType = agentType.replace(/[^a-z0-9-]/gi, '_').toUpperCase();
    const filename = `${timestamp}_${sanitizedAgentType}_${agentId.substring(0, 8)}_REPORT.md`;
    const reportPath = path.join(reportsDir, filename);

    // Save report
    fs.writeFileSync(reportPath, report, 'utf8');

    console.log(`[subagent-report-generator] Report saved: ${filename}`);
    return filename;
  } catch (error) {
    console.error('[subagent-report-generator] Error saving report:', error);
    return null;
  }
}

/**
 * Main hook function
 */
export async function run(hookParams: SubagentStopParams): Promise<{ message?: string }> {
  try {
    console.log(`[subagent-report-generator] SubagentStop triggered for agent: ${hookParams.agent_id}`);

    // Load transcript
    const messages = loadTranscript(hookParams.agent_transcript_path);

    if (messages.length === 0) {
      console.error('[subagent-report-generator] No messages in transcript, skipping report generation');
      return {};
    }

    // Analyze transcript
    const analysis = analyzeTranscript(messages);

    // Generate report
    const report = generateReport(hookParams.agent_id, analysis);

    // Save report
    const projectRoot = hookParams.cwd;
    const filename = saveReport(projectRoot, hookParams.agent_id, analysis.agentType, report);

    if (filename) {
      // Return success message to Claude
      return {
        message: `\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n` +
                 `📊 RAPORT SUBAGENTA WYGENEROWANY\n` +
                 `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n` +
                 `✅ Agent: ${analysis.agentType}\n` +
                 `📁 Raport: _AGENT_REPORTS/${filename}\n` +
                 `📝 Zmodyfikowane pliki: ${analysis.filesModified.length}\n` +
                 `⚠️  Błędy: ${analysis.errors.length}\n` +
                 `⏱️  Czas wykonania: ~${Math.floor(analysis.duration / 60)} min\n\n` +
                 `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`
      };
    }

    return {};
  } catch (error) {
    console.error('[subagent-report-generator] Hook error:', error);
    return {
      message: `\n⚠️  [subagent-report-generator] Nie udało się wygenerować raportu: ${error}\n`
    };
  }
}

// Export for Claude Code hooks system
export default { run };
