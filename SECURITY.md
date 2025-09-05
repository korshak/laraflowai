# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Security Fixes in v1.0.1

### Critical Security Issues Fixed

1. **Code Injection Vulnerability (CVE-2024-XXXX)**
   - **Issue**: `eval()` function used in `FlowCondition.php` allowed arbitrary code execution
   - **Fix**: Replaced with safe expression parser using Symfony ExpressionLanguage
   - **Impact**: Remote code execution vulnerability eliminated

2. **Unsafe Cache Operations**
   - **Issue**: Cache operations assumed Redis driver and could cause performance issues
   - **Fix**: Added safe cache operations with proper error handling and batch processing
   - **Impact**: Improved performance and reliability

3. **Input Validation Gaps**
   - **Issue**: Missing input sanitization and validation
   - **Fix**: Added comprehensive `InputSanitizer` class with validation
   - **Impact**: Prevention of XSS and injection attacks

4. **Unsafe Serialization**
   - **Issue**: Queue jobs could deserialize arbitrary classes
   - **Fix**: Added whitelist of allowed tool classes and input sanitization
   - **Impact**: Prevention of arbitrary code execution through queue jobs

## Security Best Practices

### Input Validation
- All user inputs are sanitized using the `InputSanitizer` class
- Dangerous content is detected and rejected
- Input length is limited to prevent memory exhaustion

### Expression Evaluation
- Use `FlowCondition::simple()` for basic comparisons
- Complex expressions use Symfony ExpressionLanguage for safe evaluation
- No `eval()` or similar dangerous functions

### Tool Execution
- Only whitelisted tool classes can be executed in queue jobs
- Tool inputs are validated against schemas
- All tool outputs are sanitized

### Memory Management
- Cache operations use safe patterns
- Database queries are parameterized
- Memory cleanup is performed in batches

## Reporting Security Vulnerabilities

If you discover a security vulnerability, please report it responsibly:

1. **DO NOT** create a public GitHub issue
2. Email security@laraflowai.com with details
3. Include steps to reproduce the vulnerability
4. We will respond within 48 hours

## Security Checklist

Before using LaraFlowAI in production:

- [ ] Update to latest version (1.0.1+)
- [ ] Review and configure input validation settings
- [ ] Set up proper logging for security events
- [ ] Configure rate limiting
- [ ] Review tool permissions
- [ ] Test with security scanning tools

## Configuration Security

### Environment Variables
```env
# Set secure values
LARAFLOWAI_LOGGING_ENABLED=true
LARAFLOWAI_CACHE_ENABLED=true
LARAFLOWAI_QUEUE_ENABLED=false  # Only enable if needed
```

### Input Validation
```php
// Configure maximum input lengths
'agent' => [
    'max_role_length' => 255,
    'max_goal_length' => 1000,
],
'task' => [
    'max_description_length' => 10000,
],
```

### Tool Security
```php
// Only allow specific tools in production
$allowedTools = [
    \LaraFlowAI\Tools\HttpTool::class,
    \LaraFlowAI\Tools\DatabaseTool::class,
    // Add only trusted tools
];
```

## Security Updates

- **v1.0.1**: Critical security fixes
- **v1.0.0**: Initial release (not recommended for production)

## Contact

- Security Team: security@laraflowai.com
- General Support: support@laraflowai.com
- GitHub Issues: For non-security bugs only
