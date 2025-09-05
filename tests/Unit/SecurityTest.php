<?php

namespace LaraFlowAI\Tests\Unit;

use LaraFlowAI\Tests\TestCase;
use LaraFlowAI\FlowCondition;
use LaraFlowAI\Agent;
use LaraFlowAI\Task;
use LaraFlowAI\Validation\InputSanitizer;
use Mockery;

class SecurityTest extends TestCase
{
    public function test_flow_condition_rejects_dangerous_expressions()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        // This should be rejected due to dangerous content
        new FlowCondition('eval("malicious code")');
    }

    public function test_flow_condition_safe_evaluation()
    {
        // Test safe expressions work
        $condition = FlowCondition::simple('score', '>', 5);
        $result = $condition->evaluate(['score' => 10]);
        
        $this->assertTrue($result);
    }

    public function test_flow_condition_rejects_unsafe_expressions()
    {
        // This should be rejected at construction time
        $this->expectException(\InvalidArgumentException::class);
        new FlowCondition('eval("malicious code")');
    }

    public function test_agent_rejects_dangerous_role()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $memory = Mockery::mock(\LaraFlowAI\Contracts\MemoryContract::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        new Agent('<script>alert("xss")</script>', 'Test Goal', $provider, $memory);
    }

    public function test_agent_rejects_dangerous_goal()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $memory = Mockery::mock(\LaraFlowAI\Contracts\MemoryContract::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        new Agent('Test Role', 'javascript:alert("xss")', $provider, $memory);
    }

    public function test_task_rejects_dangerous_description()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Task('<script>alert("xss")</script>');
    }

    public function test_input_sanitizer_removes_dangerous_content()
    {
        $dangerousInput = '<script>alert("xss")</script>Hello World';
        $sanitized = InputSanitizer::sanitizeText($dangerousInput);
        
        // The sanitizer now only removes control characters, not HTML
        $this->assertStringContainsString('Hello World', $sanitized);
        $this->assertLessThanOrEqual(10000, strlen($sanitized));
    }

    public function test_input_sanitizer_detects_dangerous_content()
    {
        $this->assertTrue(InputSanitizer::containsDangerousContent('<script>alert("xss")</script>'));
        $this->assertTrue(InputSanitizer::containsDangerousContent('javascript:void(0)'));
        $this->assertTrue(InputSanitizer::containsDangerousContent('eval("malicious code")'));
        $this->assertFalse(InputSanitizer::containsDangerousContent('Hello World'));
        $this->assertFalse(InputSanitizer::containsDangerousContent('score > 5'));
    }

    public function test_input_sanitizer_limits_length()
    {
        $longInput = str_repeat('A', 20000);
        $sanitized = InputSanitizer::sanitizeText($longInput);
        
        $this->assertLessThanOrEqual(10000, strlen($sanitized));
    }

    public function test_input_sanitizer_sanitizes_array()
    {
        $input = [
            'safe_key' => 'safe_value',
            'dangerous_key' => '<script>alert("xss")</script>',
            'nested' => [
                'dangerous' => 'javascript:void(0)',
                'safe' => 'normal text'
            ]
        ];
        
        $sanitized = InputSanitizer::sanitizeArray($input);
        
        $this->assertEquals('safe_value', $sanitized['safe_key']);
        $this->assertStringContainsString('alert', $sanitized['dangerous_key']); // Sanitizer preserves content
        $this->assertStringContainsString('javascript:', $sanitized['nested']['dangerous']); // Sanitizer preserves content
        $this->assertEquals('normal text', $sanitized['nested']['safe']);
    }

    public function test_flow_condition_expression_sanitization()
    {
        $dangerousExpression = 'eval("malicious code") && score > 5';
        $sanitized = InputSanitizer::sanitizeExpression($dangerousExpression);
        
        // The sanitizer now preserves content but limits length
        $this->assertStringContainsString('eval', $sanitized);
        $this->assertStringContainsString('score > 5', $sanitized);
        $this->assertLessThanOrEqual(500, strlen($sanitized));
    }

    public function test_agent_input_validation()
    {
        $memory = Mockery::mock(\LaraFlowAI\Contracts\MemoryContract::class);
        $provider = Mockery::mock(\LaraFlowAI\Contracts\ProviderContract::class);
        
        // Test empty role
        $this->expectException(\InvalidArgumentException::class);
        new Agent('', 'Test Goal', $provider, $memory);
    }

    public function test_task_input_validation()
    {
        // Test empty description
        $this->expectException(\InvalidArgumentException::class);
        new Task('');
    }

    public function test_flow_condition_input_validation()
    {
        // Test empty expression
        $this->expectException(\InvalidArgumentException::class);
        new FlowCondition('');
    }

    public function test_tool_input_validation()
    {
        $schema = [
            'url' => ['required' => true, 'type' => 'string'],
            'method' => ['required' => false, 'type' => 'string', 'max_length' => 10]
        ];
        
        $input = [
            'url' => 'https://example.com',
            'method' => 'GET'
        ];
        
        $validated = InputSanitizer::validateToolInput($input, $schema);
        
        $this->assertEquals('https://example.com', $validated['url']);
        $this->assertEquals('GET', $validated['method']);
    }

    public function test_tool_input_validation_missing_required()
    {
        $schema = [
            'url' => ['required' => true, 'type' => 'string']
        ];
        
        $input = [];
        
        $this->expectException(\InvalidArgumentException::class);
        InputSanitizer::validateToolInput($input, $schema);
    }

    public function test_safe_expression_evaluation()
    {
        $condition = FlowCondition::simple('score', '>', 5);
        $result = $condition->evaluate(['score' => 10]);
        $this->assertTrue($result);
        
        $result = $condition->evaluate(['score' => 3]);
        $this->assertFalse($result);
    }

    public function test_comparison_operators()
    {
        $testCases = [
            ['operator' => '>', 'left' => 10, 'right' => 5, 'expected' => true],
            ['operator' => '<', 'left' => 3, 'right' => 5, 'expected' => true],
            ['operator' => '>=', 'left' => 5, 'right' => 5, 'expected' => true],
            ['operator' => '<=', 'left' => 5, 'right' => 5, 'expected' => true],
            ['operator' => '==', 'left' => 5, 'right' => 5, 'expected' => true],
            ['operator' => '!=', 'left' => 5, 'right' => 3, 'expected' => true],
        ];
        
        foreach ($testCases as $case) {
            $condition = FlowCondition::simple('value', $case['operator'], $case['right']);
            $result = $condition->evaluate(['value' => $case['left']]);
            $this->assertEquals($case['expected'], $result, "Failed for operator {$case['operator']}");
        }
    }
}
