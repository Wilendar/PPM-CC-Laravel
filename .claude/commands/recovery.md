# Recovery Command

Show session recovery options when previous session was interrupted.

## Execution

Run the post-autocompact recovery script to display:
- Previous session context
- TODO status summary
- Continuation options

```bash
pwsh -NoProfile -ExecutionPolicy Bypass -File "_TOOLS\post_autocompact_recovery.ps1"
```

After showing the recovery information, ask the user which option they prefer:
- A) Continue from interruption
- B) Update plan
- C) Start new task
- D) Review full context
