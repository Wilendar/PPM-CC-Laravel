<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset;

/**
 * Validates CSS syntax and content for stylesets.
 *
 * Performs:
 * - Basic syntax validation
 * - Bracket matching
 * - Dangerous pattern detection
 * - Namespace compliance checking
 */
class StylesetValidator
{
    /**
     * Dangerous CSS patterns that should be blocked.
     */
    private array $dangerousPatterns = [
        '/expression\s*\(/i',           // IE expression()
        '/javascript\s*:/i',             // javascript: URLs
        '/behavior\s*:/i',               // IE behaviors
        '/-moz-binding/i',               // Firefox XBL
        '/url\s*\(\s*["\']?data:/i',     // Data URLs (potential XSS)
    ];

    /**
     * Validate CSS syntax.
     *
     * @param string $css CSS to validate
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validate(string $css): array
    {
        $errors = [];
        $warnings = [];

        // Check for dangerous patterns
        $dangerousCheck = $this->checkDangerousPatterns($css);
        $errors = array_merge($errors, $dangerousCheck);

        // Check bracket matching
        $bracketCheck = $this->checkBracketMatching($css);
        if ($bracketCheck) {
            $errors[] = $bracketCheck;
        }

        // Check for common syntax issues
        $syntaxCheck = $this->checkSyntax($css);
        $errors = array_merge($errors, $syntaxCheck['errors']);
        $warnings = array_merge($warnings, $syntaxCheck['warnings']);

        // Check for empty rules
        $emptyRules = $this->checkEmptyRules($css);
        $warnings = array_merge($warnings, $emptyRules);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate CSS variable value.
     *
     * @param string $name Variable name
     * @param string $value Variable value
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateVariable(string $name, string $value): array
    {
        // Check for dangerous patterns in value
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return [
                    'valid' => false,
                    'error' => "Variable '{$name}' contains potentially dangerous content",
                ];
            }
        }

        // Color validation for color variables
        if (str_contains($name, 'color') || str_contains($name, 'background')) {
            if (!$this->isValidColor($value)) {
                return [
                    'valid' => false,
                    'error' => "Variable '{$name}' has invalid color value: {$value}",
                ];
            }
        }

        // Font family validation
        if (str_contains($name, 'font-family')) {
            if (!$this->isValidFontFamily($value)) {
                return [
                    'valid' => false,
                    'error' => "Variable '{$name}' has invalid font-family value",
                ];
            }
        }

        // Size validation for spacing/size variables
        if (str_contains($name, 'spacing') || str_contains($name, 'size') || str_contains($name, 'radius')) {
            if (!$this->isValidSize($value)) {
                return [
                    'valid' => false,
                    'error' => "Variable '{$name}' has invalid size value: {$value}",
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Check namespace compliance.
     *
     * @param string $css CSS to check
     * @param string $namespace Expected namespace
     * @return array List of non-compliant selectors
     */
    public function checkNamespaceCompliance(string $css, string $namespace): array
    {
        $violations = [];

        // Extract selectors
        preg_match_all('/([.#][\w-]+)\s*\{/', $css, $matches);

        foreach ($matches[1] as $selector) {
            // Skip if starts with namespace
            if (str_starts_with(ltrim($selector, '.#'), $namespace)) {
                continue;
            }

            // Skip common exceptions
            if (in_array($selector, [':root', '*', 'html', 'body'])) {
                continue;
            }

            $violations[] = "Selector '{$selector}' does not follow namespace '{$namespace}-*'";
        }

        return $violations;
    }

    /**
     * Check for dangerous CSS patterns.
     */
    private function checkDangerousPatterns(string $css): array
    {
        $errors = [];

        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $css)) {
                $errors[] = "CSS contains potentially dangerous pattern: {$pattern}";
            }
        }

        return $errors;
    }

    /**
     * Check bracket matching.
     */
    private function checkBracketMatching(string $css): ?string
    {
        $openCurly = substr_count($css, '{');
        $closeCurly = substr_count($css, '}');

        if ($openCurly !== $closeCurly) {
            return "Bracket mismatch: {$openCurly} opening '{' vs {$closeCurly} closing '}'";
        }

        $openParen = substr_count($css, '(');
        $closeParen = substr_count($css, ')');

        if ($openParen !== $closeParen) {
            return "Parenthesis mismatch: {$openParen} opening '(' vs {$closeParen} closing ')'";
        }

        return null;
    }

    /**
     * Check for common syntax issues.
     */
    private function checkSyntax(string $css): array
    {
        $errors = [];
        $warnings = [];

        // Check for missing semicolons (basic check)
        if (preg_match('/[a-z0-9%]\s*\n\s*[a-z-]+\s*:/i', $css)) {
            $warnings[] = "Possible missing semicolon detected";
        }

        // Check for invalid property syntax
        if (preg_match('/:\s*;/', $css)) {
            $errors[] = "Empty property value detected (: ;)";
        }

        // Check for double colons in properties (not pseudo-elements)
        if (preg_match('/[^:]::[^a-z]/i', $css)) {
            $warnings[] = "Unusual double colon detected (not a pseudo-element)";
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Check for empty rules.
     */
    private function checkEmptyRules(string $css): array
    {
        $warnings = [];

        // Find empty rule blocks
        if (preg_match_all('/([^{}]+)\s*\{\s*\}/', $css, $matches)) {
            foreach ($matches[1] as $selector) {
                $warnings[] = "Empty rule block for selector: " . trim($selector);
            }
        }

        return $warnings;
    }

    /**
     * Validate color value.
     */
    private function isValidColor(string $value): bool
    {
        $value = trim($value);

        // Hex colors
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
            return true;
        }

        // RGB/RGBA
        if (preg_match('/^rgba?\s*\([\d\s,%.]+\)$/', $value)) {
            return true;
        }

        // HSL/HSLA
        if (preg_match('/^hsla?\s*\([\d\s,%deg.]+\)$/', $value)) {
            return true;
        }

        // CSS variable reference
        if (preg_match('/^var\s*\(--[\w-]+/', $value)) {
            return true;
        }

        // Named colors (common ones)
        $namedColors = ['transparent', 'inherit', 'currentColor', 'white', 'black'];
        if (in_array(strtolower($value), $namedColors)) {
            return true;
        }

        return false;
    }

    /**
     * Validate font-family value.
     */
    private function isValidFontFamily(string $value): bool
    {
        $value = trim($value);

        // Should have at least some valid font name
        if (empty($value)) {
            return false;
        }

        // Check for dangerous content
        foreach ($this->dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }

        // Basic structure check (font names or generic families)
        if (preg_match('/^[a-zA-Z\s,"\'-]+$/', $value)) {
            return true;
        }

        // CSS variable
        if (str_starts_with($value, 'var(')) {
            return true;
        }

        return false;
    }

    /**
     * Validate size value.
     */
    private function isValidSize(string $value): bool
    {
        $value = trim($value);

        // Number with unit
        if (preg_match('/^-?[\d.]+\s*(px|em|rem|%|vh|vw|vmin|vmax|ch|ex|cm|mm|in|pt|pc)?$/', $value)) {
            return true;
        }

        // CSS calc()
        if (preg_match('/^calc\s*\(.+\)$/', $value)) {
            return true;
        }

        // CSS variable
        if (str_starts_with($value, 'var(')) {
            return true;
        }

        // Keywords
        if (in_array($value, ['auto', 'inherit', 'initial', 'unset', '0'])) {
            return true;
        }

        return false;
    }
}
