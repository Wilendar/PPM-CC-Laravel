# 🔍 INSTRUKCJE WERYFIKACJI KODU PRZEZ MCP CODEX

**Wersja:** 2.0 z MCP Codex Integration  
**Data:** 2025-01-11  
**Projekt:** PPM-CC-Laravel  
**Przeznaczenie:** Comprehensive code verification procedures z wykorzystaniem MCP Codex

---

## 🎯 FILOZOFIA WERYFIKACJI KODU

### KRYTYCZNE ZASADY:
1. **KAŻDY kod MUSI być zweryfikowany** przez MCP Codex przed deploymentem
2. **SECURITY-FIRST approach** - security checks są priorytetem #1
3. **PERFORMANCE verification** - kod musi być zoptymalizowany dla shared hosting
4. **ENTERPRISE standards** - kod musi spełniać najwyższe standardy jakości
5. **ZERO TOLERANCE dla vulnerabilities** - każde security issue musi być naprawione

---

## 🛠️ WERYFIKACJA 1: STANDARD CODE VERIFICATION

### Podstawowa Weryfikacja Kodu
```javascript
const standardCodeVerification = async (filePaths, codeType) => {
    const result = await mcp__codex__codex({
        prompt: `Przeprowadź comprehensive code verification dla PPM-CC-Laravel.

PLIKI DO WERYFIKACJI:
${filePaths.map(path => `- ${path}`).join('\n')}

TYP KODU: ${codeType} (Laravel/Frontend/Database/API)

KRYTERIA WERYFIKACJI:

🔒 SECURITY (KRYTYCZNE):
1. SQL injection prevention (Eloquent ORM usage)
2. XSS protection (proper input sanitization)
3. CSRF token validation
4. Authentication & authorization checks
5. Input validation completeness
6. Output encoding/escaping
7. File upload security
8. API rate limiting implementation

⚡ PERFORMANCE:
1. Database query optimization (N+1 problem prevention)
2. Proper eager loading usage
3. Cache strategy implementation
4. Memory usage optimization
5. Shared hosting compatibility
6. Large dataset handling efficiency

🏗️ ARCHITECTURE & QUALITY:
1. Laravel 12.x best practices adherence
2. PSR-12 coding standards compliance
3. SOLID principles implementation
4. DRY principle adherence
5. Proper error handling
6. Comprehensive logging
7. Unit testability
8. Documentation completeness

🔧 PPM-CC-LARAVEL SPECIFIC:
1. Multi-store compatibility
2. 7-level permission system integration
3. 8 price groups handling
4. Multi-warehouse support
5. ERP integration compatibility
6. Prestashop API standards compliance

ZWRÓĆ:
- Security vulnerability report (HIGH/MEDIUM/LOW priority)
- Performance optimization recommendations
- Code quality improvements needed
- Compliance issues with Laravel/PPM standards
- Specific line-by-line fixes required
- Overall code quality score (1-10)`,
        
        model: "sonnet", // thorough verification
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return result;
};
```

---

## 🚨 WERYFIKACJA 2: SECURITY-FOCUSED VERIFICATION

### Deep Security Analysis
```javascript
const securityFocusedVerification = async (filePaths, userInputPoints) => {
    const result = await mcp__codex__codex({
        prompt: `Przeprowadź DEEP SECURITY ANALYSIS dla PPM-CC-Laravel.

PLIKI: ${filePaths.join(', ')}
USER INPUT POINTS: ${userInputPoints.join(', ')}

🔴 CRITICAL SECURITY CHECKS:

1. INJECTION ATTACKS:
   - SQL Injection (sprawdź raw queries)
   - NoSQL Injection (jeśli applicable)
   - LDAP Injection
   - Command Injection
   - Code Injection

2. AUTHENTICATION VULNERABILITIES:
   - Session management flaws
   - Password storage security
   - OAuth implementation issues
   - JWT token security
   - Multi-factor authentication gaps

3. AUTHORIZATION FLAWS:
   - Privilege escalation possibilities
   - Horizontal access control bypasses
   - Vertical access control bypasses
   - Role-based access control gaps
   - API endpoint authorization

4. DATA VALIDATION & SANITIZATION:
   - Input validation completeness
   - Output encoding consistency
   - File upload restrictions
   - Data type validation
   - Business logic validation

5. CRYPTOGRAPHIC ISSUES:
   - Weak encryption algorithms
   - Hardcoded cryptographic keys
   - Insufficient key management
   - Weak random number generation
   - Certificate validation issues

6. SESSION MANAGEMENT:
   - Session hijacking prevention
   - Session fixation protection
   - Secure session configuration
   - Session timeout handling
   - Cross-site request forgery protection

ANALIZA KONTEKSTOWA PPM-CC-Laravel:
- Multi-tenant security isolation
- ERP integration security boundaries
- Prestashop API secure communication
- File upload security (images, XLSX, documents)
- Admin panel security hardening
- Database access layer security

ZWRÓĆ DETAILED SECURITY REPORT:
- CRITICAL vulnerabilities (immediate fix required)
- HIGH priority issues (fix within 24h)
- MEDIUM priority issues (fix within week)
- Security best practices recommendations
- Specific code fixes with secure alternatives`,
        
        model: "opus", // security requires deep analysis
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return result;
};
```

---

## 🚀 WERYFIKACJA 3: PERFORMANCE OPTIMIZATION

### Performance-Focused Analysis
```javascript
const performanceVerification = async (filePaths, expectedLoad) => {
    const result = await mcp__codex__codex({
        prompt: `Przeprowadź PERFORMANCE OPTIMIZATION ANALYSIS dla PPM-CC-Laravel.

PLIKI: ${filePaths.join(', ')}
EXPECTED LOAD: ${expectedLoad} (concurrent users, data volume)

⚡ PERFORMANCE ANALYSIS AREAS:

1. DATABASE PERFORMANCE:
   - Query optimization (execution plans)
   - Index usage efficiency
   - N+1 query problem detection
   - Eager vs lazy loading strategy
   - Database connection pooling
   - Query caching opportunities

2. APPLICATION PERFORMANCE:
   - Memory usage patterns
   - CPU intensive operations
   - Large object handling
   - Garbage collection optimization
   - Response time bottlenecks

3. CACHING STRATEGIES:
   - Model caching implementation
   - Query result caching
   - Template caching (Blade)
   - API response caching
   - File system caching

4. FRONTEND PERFORMANCE:
   - Livewire component optimization
   - Alpine.js performance patterns
   - Asset optimization (CSS/JS)
   - Image loading strategies
   - Page load time optimization

5. SHARED HOSTING CONSIDERATIONS:
   - Resource usage limitations
   - Memory limit compliance
   - Execution time optimization
   - File system I/O efficiency
   - Process spawning optimization

PPM-CC-Laravel SPECIFIC OPTIMIZATIONS:
- Large product catalog handling (10k+ products)
- Multi-store data synchronization performance
- XLSX import/export optimization
- Image processing efficiency
- Search functionality performance
- Bulk operations optimization

ZWRÓĆ PERFORMANCE REPORT:
- Performance bottlenecks identification
- Specific optimization recommendations
- Code refactoring suggestions
- Caching strategy improvements
- Database optimization queries
- Expected performance improvements (metrics)`,
        
        model: "sonnet",
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return result;
};
```

---

## 🏗️ WERYFIKACJA 4: ARCHITECTURE & BEST PRACTICES

### Architecture Compliance Check
```javascript
const architectureVerification = async (filePaths, componentType) => {
    const result = await mcp__codex__codex({
        prompt: `Przeprowadź ARCHITECTURE & BEST PRACTICES verification dla PPM-CC-Laravel.

PLIKI: ${filePaths.join(', ')}
COMPONENT TYPE: ${componentType}

🏗️ ARCHITECTURE VERIFICATION:

1. LARAVEL 12.x COMPLIANCE:
   - Latest framework features usage
   - Deprecation warnings check
   - Modern Laravel patterns
   - Service container usage
   - Event system implementation

2. DESIGN PATTERNS:
   - Repository pattern implementation
   - Service layer architecture
   - Factory pattern usage
   - Observer pattern implementation
   - Strategy pattern where applicable

3. SOLID PRINCIPLES:
   - Single Responsibility Principle
   - Open/Closed Principle
   - Liskov Substitution Principle
   - Interface Segregation Principle
   - Dependency Inversion Principle

4. CODE ORGANIZATION:
   - Proper namespace usage
   - Class and method organization
   - Configuration management
   - Environment-specific settings
   - Dependency management

5. ERROR HANDLING:
   - Exception handling completeness
   - Error logging strategy
   - User-friendly error messages
   - Graceful degradation
   - Recovery mechanisms

6. TESTING STRATEGY:
   - Unit test coverage
   - Integration test presence
   - Test data management
   - Mock usage appropriateness
   - Test maintainability

PPM-CC-Laravel ARCHITECTURE COMPLIANCE:
- Multi-tenant architecture patterns
- Service-oriented architecture
- Event-driven communication
- Queue system integration
- API design consistency
- Data flow architecture

ZWRÓĆ ARCHITECTURE REPORT:
- Architecture compliance score
- Design pattern improvements
- SOLID principles violations
- Refactoring recommendations
- Best practices implementation gaps
- Long-term maintainability assessment`,
        
        model: "opus", // architecture needs deep understanding
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return result;
};
```

---

## 📋 WERYFIKACJA 5: COMPREHENSIVE VERIFICATION WORKFLOW

### Complete Verification Process
```javascript
const comprehensiveVerification = async (changedFiles, verificationContext) => {
    const results = {
        standard: null,
        security: null,
        performance: null,
        architecture: null,
        finalReport: null
    };
    
    // Step 1: Standard verification
    results.standard = await standardCodeVerification(
        changedFiles, 
        verificationContext.codeType
    );
    
    // Step 2: Security-focused analysis
    results.security = await securityFocusedVerification(
        changedFiles,
        verificationContext.userInputPoints
    );
    
    // Step 3: Performance analysis
    results.performance = await performanceVerification(
        changedFiles,
        verificationContext.expectedLoad
    );
    
    // Step 4: Architecture verification
    results.architecture = await architectureVerification(
        changedFiles,
        verificationContext.componentType
    );
    
    // Step 5: Generate final comprehensive report
    results.finalReport = await mcp__codex__codex({
        prompt: `Wygeneruj COMPREHENSIVE VERIFICATION REPORT dla PPM-CC-Laravel.

REZULTATY WERYFIKACJI:

STANDARD VERIFICATION:
${results.standard}

SECURITY ANALYSIS:
${results.security}

PERFORMANCE ANALYSIS:
${results.performance}

ARCHITECTURE REVIEW:
${results.architecture}

ZWRÓĆ FINAL REPORT:
1. EXECUTIVE SUMMARY
   - Overall code quality score (1-10)
   - Critical issues count
   - Ready for production? (YES/NO with reasons)

2. PRIORITY ACTION ITEMS
   - CRITICAL (must fix before deploy)
   - HIGH (fix within 24h)
   - MEDIUM (fix within week)
   - LOW (technical debt)

3. SPECIFIC FIXES REQUIRED
   - Line-by-line code changes
   - Configuration updates needed
   - Additional files to create/modify

4. VERIFICATION CHECKLIST
   - [ ] Security vulnerabilities fixed
   - [ ] Performance optimized
   - [ ] Architecture compliant
   - [ ] Tests updated
   - [ ] Documentation updated

5. DEPLOYMENT READINESS
   - Pre-deployment checks
   - Post-deployment verification
   - Rollback procedures if needed`,
        
        model: "opus",
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return results;
};
```

---

## 🔄 WERYFIKACJA 6: PRE-DEPLOYMENT FINAL CHECK

### Final Pre-Deployment Verification
```javascript
const preDeploymentVerification = async (deploymentPackage) => {
    const result = await mcp__codex__codex({
        prompt: `Przeprowadź FINAL PRE-DEPLOYMENT VERIFICATION dla PPM-CC-Laravel.

DEPLOYMENT PACKAGE ANALYSIS:
${deploymentPackage}

🚀 DEPLOYMENT READINESS CHECK:

1. CODE QUALITY GATES:
   - All security vulnerabilities fixed? ✓/✗
   - Performance optimized for production? ✓/✗
   - Error handling comprehensive? ✓/✗
   - Logging properly configured? ✓/✗

2. CONFIGURATION VERIFICATION:
   - Environment variables secure? ✓/✗
   - Database configuration correct? ✓/✗
   - Cache configuration optimized? ✓/✗
   - Session configuration secure? ✓/✗

3. HOSTIDO COMPATIBILITY:
   - Shared hosting limitations respected? ✓/✗
   - File permissions correct? ✓/✗
   - Memory usage within limits? ✓/✗
   - PHP version compatibility? ✓/✗

4. INTEGRATION TESTING:
   - Database migrations tested? ✓/✗
   - API endpoints verified? ✓/✗
   - Frontend components working? ✓/✗
   - File upload functionality tested? ✓/✗

5. BACKUP & ROLLBACK:
   - Backup procedures verified? ✓/✗
   - Rollback plan documented? ✓/✗
   - Database rollback tested? ✓/✗
   - File system rollback ready? ✓/✗

FINAL DEPLOYMENT DECISION:
- GO/NO-GO recommendation
- Critical blockers if any
- Post-deployment monitoring plan
- Success criteria definition

If NO-GO: Detailed list of issues to resolve before deployment
If GO: Final checklist for deployment execution`,
        
        model: "opus",
        sandbox: "read-only",
        cwd: "D:\\OneDrive - MPP TRADE\\Skrypty\\PPM-CC-Laravel"
    });
    
    return result;
};
```

---

## 📊 WERYFIKACJA 7: QUALITY METRICS & REPORTING

### Code Quality Metrics Generation
```javascript
const generateQualityMetrics = async (verificationHistory, codebase) => {
    const result = await mcp__codex__codex({
        prompt: `Wygeneruj CODE QUALITY METRICS dla PPM-CC-Laravel.

HISTORIA WERYFIKACJI:
${verificationHistory}

AKTUALNY CODEBASE:
${codebase}

📊 METRYKI DO WYGENEROWANIA:

1. QUALITY TRENDS:
   - Code quality score over time
   - Security vulnerability trends
   - Performance improvement tracking
   - Technical debt accumulation

2. VERIFICATION EFFECTIVENESS:
   - Issues caught pre-deployment vs post-deployment
   - Time to fix critical issues
   - Recurring issue patterns
   - False positive rate

3. TEAM PERFORMANCE:
   - Code quality by developer/agent
   - Most common issue types
   - Learning curve tracking
   - Best practices adoption rate

4. BUSINESS IMPACT:
   - Deployment success rate
   - Production incidents reduction
   - Performance improvement metrics
   - User satisfaction correlation

5. PREDICTIVE ANALYSIS:
   - Areas likely to need refactoring
   - Components at risk of issues
   - Technical debt hotspots
   - Recommended investment areas

DASHBOARD RECOMMENDATIONS:
- Key performance indicators (KPIs)
- Automated alerting thresholds
- Regular reporting schedule
- Continuous improvement actions`,
        
        model: "sonnet",
        sandbox: "read-only"
    });
    
    return result;
};
```

---

## 🛡️ WERYFIKACJA 8: SPECIALIZED VERIFICATIONS

### Frontend-Specific Verification
```javascript
const frontendVerification = async (frontendFiles) => {
    return await mcp__codex__codex({
        prompt: `Specialized FRONTEND VERIFICATION dla PPM-CC-Laravel Livewire + Alpine.js components.

FILES: ${frontendFiles.join(', ')}

FRONTEND SPECIFIC CHECKS:
- Livewire 3.x best practices
- Alpine.js security patterns
- XSS prevention in templates
- CSRF token handling
- Form validation completeness
- Accessibility compliance (WCAG 2.1)
- Mobile responsiveness
- Browser compatibility
- Performance optimization`,
        model: "sonnet",
        sandbox: "read-only"
    });
};
```

### Database-Specific Verification
```javascript
const databaseVerification = async (migrationFiles, modelFiles) => {
    return await mcp__codex__codex({
        prompt: `Specialized DATABASE VERIFICATION dla PPM-CC-Laravel migrations i models.

MIGRATION FILES: ${migrationFiles.join(', ')}
MODEL FILES: ${modelFiles.join(', ')}

DATABASE SPECIFIC CHECKS:
- Migration rollback safety
- Index optimization strategy
- Foreign key constraints
- Data integrity rules
- Query performance implications
- Multi-tenant data isolation
- Backup/restore compatibility`,
        model: "sonnet",
        sandbox: "read-only"
    });
};
```

### API-Specific Verification
```javascript
const apiVerification = async (apiFiles, endpointSpecs) => {
    return await mcp__codex__codex({
        prompt: `Specialized API VERIFICATION dla PPM-CC-Laravel REST API endpoints.

API FILES: ${apiFiles.join(', ')}
ENDPOINTS: ${endpointSpecs.join(', ')}

API SPECIFIC CHECKS:
- RESTful design compliance
- Rate limiting implementation
- Authentication/authorization
- Input validation completeness
- Response format consistency
- Error handling standardization
- API versioning strategy
- Documentation accuracy`,
        model: "sonnet",
        sandbox: "read-only"
    });
};
```

---

## ✅ VERIFICATION CHECKLIST TEMPLATE

### Standard Verification Checklist
```markdown
## 🔍 VERIFICATION CHECKLIST - [FEATURE_NAME]

### SECURITY ✅
- [ ] Input validation comprehensive
- [ ] SQL injection prevention verified
- [ ] XSS protection implemented
- [ ] CSRF tokens properly used
- [ ] Authentication checks in place
- [ ] Authorization levels respected
- [ ] File upload restrictions secure
- [ ] Sensitive data properly encrypted

### PERFORMANCE ✅
- [ ] Database queries optimized
- [ ] N+1 problem prevented
- [ ] Caching strategy implemented
- [ ] Memory usage reasonable
- [ ] Response times acceptable
- [ ] Large dataset handling efficient
- [ ] Shared hosting compatible

### QUALITY ✅
- [ ] Laravel 12.x best practices
- [ ] PSR-12 coding standards
- [ ] SOLID principles applied
- [ ] Error handling comprehensive
- [ ] Logging properly implemented
- [ ] Unit tests written/updated
- [ ] Documentation updated

### PPM-CC-Laravel SPECIFIC ✅
- [ ] Multi-store compatibility
- [ ] 7-level permissions respected
- [ ] 8 price groups handled
- [ ] Multi-warehouse support
- [ ] ERP integration ready
- [ ] Prestashop API compliant

### DEPLOYMENT READINESS ✅
- [ ] Configuration reviewed
- [ ] Environment variables set
- [ ] Migration tested
- [ ] Rollback plan ready
- [ ] Monitoring configured
```

---

## 🚀 PODSUMOWANIE

**MCP Codex Code Verification zapewnia:**

✅ **100% Security Coverage** - wszystkie vulnerability wykryte i naprawione  
✅ **Performance Optimization** - kod zoptymalizowany dla shared hosting  
✅ **Enterprise Quality** - najwyższe standardy jakości kodu  
✅ **Automated Verification** - consistent i thorough code review  
✅ **Deployment Confidence** - zero-risk deployments  

**Result: Bulletproof code quality leading to stable, secure i performant PPM-CC-Laravel application.**