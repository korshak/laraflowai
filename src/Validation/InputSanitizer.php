<?php

namespace LaraFlowAI\Validation;

/**
 * InputSanitizer class provides methods for sanitizing and validating input data.
 * 
 * This class contains static methods for sanitizing various types of input
 * to prevent security issues and ensure data integrity. It handles text
 * sanitization, role validation, goal validation, and other input types.
 * 
 * @package LaraFlowAI\Validation
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class InputSanitizer
{
    /**
     * Maximum length for text inputs.
     * 
     * @var int
     */
    protected const MAX_TEXT_LENGTH = 10000;
    
    /**
     * Maximum length for role names.
     * 
     * @var int
     */
    protected const MAX_ROLE_LENGTH = 255;
    
    /**
     * Maximum length for goal descriptions.
     * 
     * @var int
     */
    protected const MAX_GOAL_LENGTH = 1000;

    /**
     * Sanitize text input.
     * 
     * @param string $input The input text to sanitize
     * @param int $maxLength Maximum length for the text
     * @return string The sanitized text
     */
    public static function sanitizeText(string $input, int $maxLength = self::MAX_TEXT_LENGTH): string
    {
        // Remove null bytes and control characters
        $input = str_replace(["\0", "\r", "\n", "\t"], '', $input);
        
        // Limit length to prevent memory issues
        return substr(trim($input), 0, $maxLength);
    }

    /**
     * Sanitize role name.
     * 
     * @param string $role The role name to sanitize
     * @return string The sanitized role name
     */
    public static function sanitizeRole(string $role): string
    {
        return self::sanitizeText($role, self::MAX_ROLE_LENGTH);
    }

    /**
     * Sanitize goal description.
     * 
     * @param string $goal The goal description to sanitize
     * @return string The sanitized goal description
     */
    public static function sanitizeGoal(string $goal): string
    {
        return self::sanitizeText($goal, self::MAX_GOAL_LENGTH);
    }

    /**
     * Sanitize task description.
     * 
     * @param string $description The task description to sanitize
     * @return string The sanitized task description
     */
    public static function sanitizeTaskDescription(string $description): string
    {
        return self::sanitizeText($description, self::MAX_TEXT_LENGTH);
    }

    /**
     * Sanitize array input.
     * 
     * @param array<string, mixed> $input The input array to sanitize
     * @param int $maxDepth Maximum recursion depth
     * @return array<string, mixed> The sanitized array
     */
    public static function sanitizeArray(array $input, int $maxDepth = 5): array
    {
        if ($maxDepth <= 0) {
            return [];
        }

        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitizedKey = self::sanitizeText((string) $key, 255);
            
            if (is_string($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeText($value);
            } elseif (is_array($value)) {
                $sanitized[$sanitizedKey] = self::sanitizeArray($value, $maxDepth - 1);
            } elseif (is_numeric($value) || is_bool($value) || is_null($value)) {
                $sanitized[$sanitizedKey] = $value;
            } else {
                // Convert other types to string and sanitize
                $sanitized[$sanitizedKey] = self::sanitizeText((string) $value);
            }
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize expression for FlowCondition.
     * 
     * @param string $expression The expression to sanitize
     * @return string The sanitized expression
     */
    public static function sanitizeExpression(string $expression): string
    {
        // Just limit length and trim - let the safe evaluator handle validation
        $expression = substr(trim($expression), 0, 500);
        
        return $expression;
    }

    /**
     * Validate tool input against schema.
     * 
     * @param array<string, mixed> $input The input data to validate
     * @param array<string, mixed> $schema The validation schema
     * @return array<string, mixed> The validated and sanitized input
     * 
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function validateToolInput(array $input, array $schema): array
    {
        $sanitized = [];
        
        foreach ($schema as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($input[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
            
            if (isset($input[$field])) {
                $value = $input[$field];
                
                // Sanitize based on type
                if (isset($rules['type'])) {
                    switch ($rules['type']) {
                        case 'string':
                            $sanitized[$field] = self::sanitizeText((string) $value, $rules['max_length'] ?? self::MAX_TEXT_LENGTH);
                            break;
                        case 'array':
                            $sanitized[$field] = self::sanitizeArray((array) $value);
                            break;
                        case 'integer':
                            $sanitized[$field] = (int) $value;
                            break;
                        case 'float':
                            $sanitized[$field] = (float) $value;
                            break;
                        case 'boolean':
                            $sanitized[$field] = (bool) $value;
                            break;
                        default:
                            $sanitized[$field] = self::sanitizeText((string) $value);
                    }
                } else {
                    $sanitized[$field] = self::sanitizeText((string) $value);
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Check if input contains potentially dangerous content.
     * 
     * @param string $input The input to check
     * @return bool True if dangerous content is found, false otherwise
     */
    public static function containsDangerousContent(string $input): bool
    {
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/proc_open\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize text input with less aggressive cleaning for testing.
     * 
     * @param string $input The input text to sanitize
     * @param int $maxLength Maximum length for the text
     * @return string The sanitized text
     */
    public static function sanitizeTextForTest(string $input, int $maxLength = self::MAX_TEXT_LENGTH): string
    {
        // Remove null bytes and control characters
        $input = str_replace(["\0", "\r", "\n", "\t"], '', $input);
        
        // Limit length to prevent memory issues
        return substr(trim($input), 0, $maxLength);
    }
}
