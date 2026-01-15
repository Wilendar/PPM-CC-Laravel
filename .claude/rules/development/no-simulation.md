# Development: Zero Simulation Policy

## Critical Rule
**ZERO TOLERANCE** for simulations, placeholders, mock data, or fake operations!

## Absolutely Forbidden

### Simulated Operations Without Execution
```markdown
FORBIDDEN:
- "Uploaded file.php (58 KB)" <- WITHOUT actual pscp command!
- "Cache cleared successfully" <- WITHOUT actual plink command!
- "Migration completed" <- WITHOUT actual artisan migrate!
- "Tests passed (100%)" <- WITHOUT actual php artisan test!
```

### Placeholder Data
```php
// FORBIDDEN:
$product->price = 150.0;      // Hardcoded fake price!
'value' => 'Lorem ipsum';     // Placeholder text!
'users' => 250;               // Mock count!
'status' => 'active';         // Fake status!
```

### Fake Reporting
```markdown
FORBIDDEN:
## VERIFICATION RESULTS
- All files deployed successfully <- WITHOUT verification!
- Application running correctly <- WITHOUT health check!
```

## Correct Pattern
```powershell
# 1. Execute REAL command
pscp -i "..." -P 64321 "file.php" "host@...:domains/.../file.php"

# 2. Capture REAL output
# Output: "file.php | 57 kB | 57.8 kB/s | ETA: 00:00:00 | 100%"

# 3. VERIFY on server
plink ... "ls -lh domains/.../file.php"
# Output: "-rw-rw-r-- 1 user user 57K Oct 15 14:14 file.php"

# 4. ONLY THEN report
"file.php deployed and VERIFIED on server"
```

## Rule
If you cannot execute real operation -> DO NOT REPORT SUCCESS. Report blocker and ask for help.
