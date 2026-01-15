/**
 * POST DEPLOYMENT VERIFICATION HOOK
 *
 * Runs AFTER deployment operations (pscp/plink to production)
 * Shows MANDATORY Claude in Chrome verification reminder
 *
 * CRITICAL: This hook enforces Claude in Chrome verification for ALL production deployments
 *
 * Note: Claude in Chrome is Anthropic's official Chrome extension (NOT an MCP!)
 * Functions use mcp__claude-in-chrome__* prefix as interface notation.
 *
 * Project: PPM-CC-Laravel
 * Created: 2025-11-21
 * Updated: 2025-12-18 - Migrated from Chrome DevTools MCP to Claude in Chrome
 * Related: FIX #7/#8 (category checkbox/button flashing) - Claude in Chrome prevented recurrence
 */

/**
 * Detect deployment-related Bash commands
 */
function isDeploymentCommand(command: string): boolean {
  // Normalize command (lowercase, remove extra spaces)
  const normalizedCmd = command.toLowerCase().replace(/\s+/g, ' ').trim();

  // Deployment indicators
  const deploymentPatterns = [
    'pscp',                    // File upload to Hostido
    'plink',                   // SSH command execution
    'host379076',              // Hostido hostname
    'ppm.mpptrade.pl',         // Production domain
    'artisan view:clear',      // Cache clearing
    'artisan cache:clear',     // Cache clearing
    'artisan config:clear',    // Cache clearing
  ];

  return deploymentPatterns.some(pattern => normalizedCmd.includes(pattern));
}

/**
 * Detect deployment target (CSS, JS, Blade, PHP)
 */
function detectDeploymentTarget(command: string): string[] {
  const targets: string[] = [];

  if (command.includes('.css') || command.includes('/css/')) {
    targets.push('CSS');
  }

  if (command.includes('.js') || command.includes('/js/') || command.includes('build/assets')) {
    targets.push('JS');
  }

  if (command.includes('.blade.php') || command.includes('/views/')) {
    targets.push('Blade');
  }

  if (command.includes('.php') && !command.includes('.blade.php')) {
    targets.push('PHP');
  }

  if (command.includes('Livewire') || command.includes('/livewire/')) {
    targets.push('Livewire');
  }

  if (command.includes('public/build') || command.includes('manifest.json')) {
    targets.push('Vite Assets');
  }

  // Default if nothing specific detected
  if (targets.length === 0) {
    targets.push('Production Files');
  }

  return targets;
}

/**
 * Determine verification scenario based on deployment target
 */
function determineVerificationScenario(targets: string[]): string {
  if (targets.includes('Livewire') || targets.includes('PHP')) {
    return 'SCENARIO 2: Livewire Component Verification';
  }

  if (targets.includes('CSS') || targets.includes('JS') || targets.includes('Blade') || targets.includes('Vite Assets')) {
    return 'SCENARIO 1: Post-Deployment Verification';
  }

  return 'SCENARIO 1: Post-Deployment Verification';
}

/**
 * Generate Claude in Chrome verification reminder
 */
function generateVerificationReminder(targets: string[], scenario: string): string {
  let reminder = '\n\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';
  reminder += '🚀 MANDATORY: Claude in Chrome Verification REQUIRED\n';
  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n';

  reminder += `⚠️  DEPLOYMENT DETECTED: ${targets.join(', ')}\n\n`;

  reminder += `📋 REQUIRED VERIFICATION: ${scenario}\n\n`;

  reminder += `🔍 MANDATORY STEPS:\n\n`;

  if (scenario.includes('SCENARIO 1')) {
    // Post-Deployment Verification
    reminder += `0. MANDATORY FIRST STEP - Get tab context:\n`;
    reminder += `   mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })\n`;
    reminder += `   → Get TAB_ID from response!\n\n`;

    reminder += `1. Navigate to production page:\n`;
    reminder += `   mcp__claude-in-chrome__navigate({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     url: "https://ppm.mpptrade.pl/admin/products"\n`;
    reminder += `   })\n\n`;

    reminder += `2. Find elements (token-optimized):\n`;
    reminder += `   mcp__claude-in-chrome__find({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     query: "error messages"\n`;
    reminder += `   })\n`;
    reminder += `   → Verify: No "wire:snapshot" literal text\n`;
    reminder += `   → Verify: Expected UI elements present\n\n`;

    reminder += `3. Check console for errors:\n`;
    reminder += `   mcp__claude-in-chrome__read_console_messages({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     onlyErrors: true\n`;
    reminder += `   })\n`;
    reminder += `   → Expected: 0 errors\n\n`;

    reminder += `4. Verify network requests (CSS/JS loaded):\n`;
    reminder += `   mcp__claude-in-chrome__read_network_requests({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     urlPattern: ".css"\n`;
    reminder += `   })\n`;
    reminder += `   → Expected: All HTTP 200\n\n`;

    reminder += `5. Screenshot for visual confirmation:\n`;
    reminder += `   mcp__claude-in-chrome__computer({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "screenshot"\n`;
    reminder += `   })\n\n`;
  } else if (scenario.includes('SCENARIO 2')) {
    // Livewire Component Verification
    reminder += `0. MANDATORY FIRST STEP - Get tab context:\n`;
    reminder += `   mcp__claude-in-chrome__tabs_context_mcp({ createIfEmpty: true })\n`;
    reminder += `   → Get TAB_ID from response!\n\n`;

    reminder += `1. Navigate to component page:\n`;
    reminder += `   mcp__claude-in-chrome__navigate({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     url: "https://ppm.mpptrade.pl/admin/products"\n`;
    reminder += `   })\n\n`;

    reminder += `2. Interact with Livewire component:\n`;
    reminder += `   // Find element references\n`;
    reminder += `   mcp__claude-in-chrome__find({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     query: "tab button"\n`;
    reminder += `   })\n`;
    reminder += `   // Click tab/button to trigger Livewire update\n`;
    reminder += `   mcp__claude-in-chrome__computer({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "left_click",\n`;
    reminder += `     ref: "ref_123"  // Element reference from find()\n`;
    reminder += `   })\n\n`;

    reminder += `3. CRITICAL: Check disabled states (prevent FIX #7/#8 repeat):\n`;
    reminder += `   // WAIT 6 seconds for wire:poll.5s to settle!\n`;
    reminder += `   mcp__claude-in-chrome__computer({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "wait",\n`;
    reminder += `     duration: 6\n`;
    reminder += `   })\n`;
    reminder += `   mcp__claude-in-chrome__javascript_tool({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "javascript_exec",\n`;
    reminder += `     text: "({ total: document.querySelectorAll('input').length, disabled: document.querySelectorAll('input[disabled]').length })"\n`;
    reminder += `   })\n`;
    reminder += `   → Expected: {disabled: 0} (all enabled)\n\n`;

    reminder += `4. Check Livewire component state:\n`;
    reminder += `   mcp__claude-in-chrome__javascript_tool({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "javascript_exec",\n`;
    reminder += `     text: "window.Livewire?.components?.componentsByName('product-form')?.[0]?.data"\n`;
    reminder += `   })\n`;
    reminder += `   → Verify: Component properties correct\n\n`;

    reminder += `5. Check console for Livewire errors:\n`;
    reminder += `   mcp__claude-in-chrome__read_console_messages({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     onlyErrors: true\n`;
    reminder += `   })\n`;
    reminder += `   → Expected: 0 errors\n\n`;

    reminder += `6. Screenshot final state:\n`;
    reminder += `   mcp__claude-in-chrome__computer({\n`;
    reminder += `     tabId: TAB_ID,\n`;
    reminder += `     action: "screenshot"\n`;
    reminder += `   })\n\n`;
  }

  reminder += `⚠️  DO NOT report completion to user until verification passes!\n\n`;

  reminder += `📖 REFERENCE:\n`;
  reminder += `   - Full Guide: .claude/rules/verification/chrome-devtools.md\n`;
  reminder += `   - Skill: Use "Skill(chrome-devtools-verification)" for guided workflow\n`;
  reminder += `   - Success Pattern: Deploy → Verify → Screenshot → THEN report\n\n`;

  reminder += `❌ ANTI-PATTERNS TO AVOID:\n`;
  reminder += `   - Reporting completion WITHOUT Claude in Chrome verification\n`;
  reminder += `   - Using curl/HTTP checks INSTEAD OF browser inspection\n`;
  reminder += `   - Assuming "build passed = production works"\n`;
  reminder += `   - Screenshot ONLY (need console/network verification too!)\n\n`;

  reminder += `✅ SUCCESS CRITERIA:\n`;
  reminder += `   - Snapshot shows expected UI (no wire:snapshot)\n`;
  reminder += `   - Console: 0 errors\n`;
  reminder += `   - Network: All CSS/JS HTTP 200\n`;
  reminder += `   - Disabled states: 0 (for Livewire)\n`;
  reminder += `   - Screenshot confirms visual layout\n\n`;

  reminder += `🎯 WHY THIS IS MANDATORY:\n`;
  reminder += `   FIX #7/#8 taught us: Node.js scripts miss wire:poll conflicts,\n`;
  reminder += `   disabled state flashing, and Livewire directive issues.\n`;
  reminder += `   Claude in Chrome = ONLY tool that catches these!\n\n`;

  reminder += '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n';

  return reminder;
}

/**
 * Main hook function
 */
export async function run(hookParams: {
  tool: string;
  args: any;
  result: any;
  context: any;
}): Promise<{ message?: string }> {
  try {
    const { tool, args } = hookParams;

    // Only trigger for Bash tool
    if (tool !== 'Bash') {
      return {};
    }

    // Get command
    const command = args?.command || '';
    if (!command) {
      return {};
    }

    // Check if this is a deployment command
    if (!isDeploymentCommand(command)) {
      return {};
    }

    // Detect deployment target
    const targets = detectDeploymentTarget(command);

    // Determine verification scenario
    const scenario = determineVerificationScenario(targets);

    // Generate verification reminder
    const reminder = generateVerificationReminder(targets, scenario);

    return { message: reminder };
  } catch (error) {
    console.error('[post-deployment-verification] Hook error:', error);
    return {};
  }
}

// Export for Claude Code hooks system
export default { run };
