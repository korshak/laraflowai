<?php

namespace LaraFlowAI;

use LaraFlowAI\Validation\InputSanitizer;

/**
 * FlowCondition class represents a condition that can be evaluated in a flow.
 * 
 * A flow condition can be a simple expression or a custom evaluator function.
 * It provides a way to control the execution flow based on context data
 * and can be used to create conditional logic in workflows.
 * 
 * @package LaraFlowAI
 * @author LaraFlowAI Team
 * @version 1.0.0
 * @since 1.0.0
 */
class FlowCondition
{
    /**
     * The expression to evaluate.
     * 
     * @var string
     */
    protected string $expression;

    /**
     * Variables used in the expression.
     * 
     * @var array<string, mixed>
     */
    protected array $variables = [];

    /**
     * Custom evaluator function.
     * 
     * @var callable|null
     */
    protected $evaluator = null;

    /**
     * Create a new FlowCondition instance.
     * 
     * @param string $expression The expression to evaluate
     * @param array<string, mixed> $variables Optional variables for the expression
     * 
     * @throws \InvalidArgumentException If expression is empty or contains dangerous content
     */
    public function __construct(string $expression, array $variables = [])
    {
        $this->expression = InputSanitizer::sanitizeExpression($expression);
        $this->variables = InputSanitizer::sanitizeArray($variables);
        
        // Validate inputs
        $this->validateInputs();
    }

    /**
     * Validate condition inputs.
     * 
     * @throws \InvalidArgumentException If expression is empty or contains dangerous content
     */
    protected function validateInputs(): void
    {
        if (empty($this->expression)) {
            throw new \InvalidArgumentException('Expression cannot be empty');
        }
        
        if (InputSanitizer::containsDangerousContent($this->expression)) {
            throw new \InvalidArgumentException('Expression contains potentially dangerous content');
        }
    }

    /**
     * Create a simple condition.
     * 
     * @param string $variable The variable name
     * @param string $operator The comparison operator
     * @param mixed $value The value to compare against
     * @return self A new FlowCondition instance
     */
    public static function simple(string $variable, string $operator, mixed $value): self
    {
        // Sanitize inputs
        $variable = InputSanitizer::sanitizeText($variable, 255);
        $operator = InputSanitizer::sanitizeText($operator, 10);
        
        $expression = "{$variable} {$operator} " . (is_string($value) ? "'{$value}'" : $value);
        return new self($expression, [$variable => $value]);
    }

    /**
     * Create a custom condition with evaluator.
     * 
     * @param callable $evaluator The custom evaluator function
     * @return self A new FlowCondition instance
     */
    public static function custom(callable $evaluator): self
    {
        $condition = new self('custom');
        $condition->evaluator = $evaluator;
        return $condition;
    }

    /**
     * Evaluate the condition.
     * 
     * @param array<string, mixed> $context The context data to evaluate against
     * @return bool True if condition is met, false otherwise
     */
    public function evaluate(array $context): bool
    {
        if ($this->evaluator) {
            return ($this->evaluator)($context);
        }

        return $this->evaluateExpression($context);
    }

    /**
     * Evaluate the expression safely.
     * 
     * @param array<string, mixed> $context The context data to evaluate against
     * @return bool True if expression evaluates to true, false otherwise
     */
    protected function evaluateExpression(array $context): bool
    {
        $expression = $this->expression;
        
        // Replace variables with their values from context
        foreach ($this->variables as $variable => $value) {
            $contextValue = $context[$variable] ?? null;
            $expression = str_replace($variable, $this->formatValue($contextValue), $expression);
        }

        // Safe evaluation using a proper expression parser
        try {
            // Use Symfony ExpressionLanguage for safe evaluation
            if (class_exists('\Symfony\Component\ExpressionLanguage\ExpressionLanguage')) {
                $language = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();
                return (bool) $language->evaluate($expression, $context);
            }
            
            // Fallback to safe evaluator for basic expressions
            return $this->safeEvaluate($expression, $context);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Safe evaluation for basic expressions without eval()
     */
    private function safeEvaluate(string $expression, array $context): bool
    {
        // Only allow safe mathematical and comparison operations
        $allowedPatterns = [
            '/^[a-zA-Z_][a-zA-Z0-9_]*\s*[<>=!]+\s*[\d\'\"]+$/',  // variable comparison
            '/^[\d\'\"]+\s*[<>=!]+\s*[a-zA-Z_][a-zA-Z0-9_]*$/',  // value comparison
            '/^[a-zA-Z_][a-zA-Z0-9_]*\s*[<>=!]+\s*[a-zA-Z_][a-zA-Z0-9_]*$/', // variable comparison
        ];
        
        $expression = trim($expression);
        
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                return $this->evaluateComparison($expression, $context);
            }
        }
        
        // If no pattern matches, try to evaluate as a simple comparison
        if (preg_match('/^(.+?)\s*([<>=!]+)\s*(.+)$/', $expression, $matches)) {
            $left = trim($matches[1]);
            $operator = trim($matches[2]);
            $right = trim($matches[3]);
            
            $leftValue = $this->getValueFromContext($left, $context);
            $rightValue = $this->getValueFromContext($right, $context);
            
            return $this->compareValues($leftValue, $operator, $rightValue);
        }
        
        return false; // Reject unsafe expressions
    }

    /**
     * Evaluate simple comparison expressions safely
     */
    private function evaluateComparison(string $expression, array $context): bool
    {
        // Extract operator and operands
        if (preg_match('/^(.+?)\s*([<>=!]+)\s*(.+)$/', $expression, $matches)) {
            $left = trim($matches[1]);
            $operator = trim($matches[2]);
            $right = trim($matches[3]);
            
            // Get values from context or use literal values
            $leftValue = $this->getValueFromContext($left, $context);
            $rightValue = $this->getValueFromContext($right, $context);
            
            // Perform safe comparison
            return $this->compareValues($leftValue, $operator, $rightValue);
        }
        
        return false;
    }

    /**
     * Get value from context or parse literal value
     */
    private function getValueFromContext(string $value, array $context)
    {
        // Remove quotes if present
        $value = trim($value, '\'"');
        
        // Check if it's a context variable
        if (isset($context[$value])) {
            return $context[$value];
        }
        
        // Try to parse as number
        if (is_numeric($value)) {
            return is_float($value) ? (float) $value : (int) $value;
        }
        
        // Return as string
        return $value;
    }

    /**
     * Compare two values safely
     */
    private function compareValues($left, string $operator, $right): bool
    {
        switch ($operator) {
            case '==':
            case '=':
                return $left == $right;
            case '!=':
            case '<>':
                return $left != $right;
            case '>':
                return $left > $right;
            case '<':
                return $left < $right;
            case '>=':
                return $left >= $right;
            case '<=':
                return $left <= $right;
            default:
                return false;
        }
    }

    /**
     * Format value for expression
     */
    protected function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return "'{$value}'";
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        return (string) $value;
    }

    /**
     * Get expression.
     * 
     * @return string The expression string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get variables.
     * 
     * @return array<string, mixed> The variables array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Set variables.
     * 
     * @param array<string, mixed> $variables The variables to set
     * @return self Returns the condition instance for method chaining
     */
    public function setVariables(array $variables): self
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Add a variable.
     * 
     * @param string $name The variable name
     * @param mixed $value The variable value
     * @return self Returns the condition instance for method chaining
     */
    public function addVariable(string $name, mixed $value): self
    {
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * Get evaluator.
     * 
     * @return callable|null The evaluator function if set, null otherwise
     */
    public function getEvaluator()
    {
        return $this->evaluator;
    }
}
