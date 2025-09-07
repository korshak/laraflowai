# LaraFlowAI Artisan Commands

This document describes the Artisan commands available in the LaraFlowAI package for scaffolding and quick testing.

## Available Commands

### 1. `php artisan laraflowai:make:agent`

Creates a new Agent class with customizable role, goal, tools, and memory settings.

#### Usage

```bash
# Basic usage
php artisan laraflowai:make:agent WriterAgent

# With options
php artisan laraflowai:make:agent ResearchAgent --role=researcher --goal="Conduct thorough research on given topics" --tools=http,database --memory=true

# Force overwrite existing file
php artisan laraflowai:make:agent WriterAgent --force
```

#### Options

- `--role`: The role of the agent (e.g., writer, researcher, analyst)
- `--goal`: The goal of the agent
- `--tools`: Comma-separated list of tools to include (http, database, filesystem)
- `--memory`: Enable memory for the agent (true/false)
- `--force`: Overwrite existing files

#### Generated File Structure

The command creates a new Agent class in `app/LaraFlowAI/Agents/` with:

- Proper namespace and imports
- Constructor with role, goal, and memory settings
- Tool setup methods
- Customizable response processing
- Usage examples in comments

#### Example Generated Code

```php
<?php

namespace App\LaraFlowAI\Agents;

use LaraFlowAI\Agent;
use LaraFlowAI\Contracts\ToolContract;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;

class WriterAgent extends Agent
{
    public function __construct()
    {
        parent::__construct(
            role: 'writer',
            goal: 'Create high-quality written content',
            memory: true
        );

        $this->setupTools();
    }

    protected function setupTools(): void
    {
        $this->addTool('http', new HttpTool());
        $this->addTool('database', new DatabaseTool());
    }

    // ... additional methods
}
```

### 2. `php artisan laraflowai:make:crew`

Creates a new Crew class that coordinates multiple agents working together.

#### Usage

```bash
# Basic usage
php artisan laraflowai:make:crew ContentCrew

# With agents and tasks
php artisan laraflowai:make:crew MarketingCrew --agents=WriterAgent,ResearcherAgent --tasks="Create content,Research topics" --parallel=true

# With memory
php artisan laraflowai:make:crew AnalysisCrew --memory=true
```

#### Options

- `--agents`: Comma-separated list of agent classes to include
- `--tasks`: Comma-separated list of task descriptions
- `--memory`: Enable memory for the crew (true/false)
- `--parallel`: Enable parallel execution (true/false)
- `--force`: Overwrite existing files

#### Generated File Structure

The command creates a new Crew class in `app/LaraFlowAI/Crews/` with:

- Proper namespace and imports
- Constructor with memory and parallel settings
- Agent and task setup methods
- Customizable result processing
- Usage examples in comments

### 3. `php artisan laraflowai:make:flow`

Creates a new Flow class for managing multi-step workflows with conditions and events.

#### Usage

```bash
# Basic usage
php artisan laraflowai:make:flow ContentFlow

# With steps and conditions
php artisan laraflowai:make:flow PublishingFlow --steps="Draft content,Review content,Publish content" --conditions="Quality check,Approval required"

# With events
php artisan laraflowai:make:flow NotificationFlow --events="content_created,content_published" --memory=true
```

#### Options

- `--steps`: Comma-separated list of step descriptions
- `--conditions`: Comma-separated list of condition descriptions
- `--memory`: Enable memory for the flow (true/false)
- `--events`: Comma-separated list of event names
- `--force`: Overwrite existing files

#### Generated File Structure

The command creates a new Flow class in `app/LaraFlowAI/Flows/` with:

- Proper namespace and imports
- Constructor with memory settings
- Step, condition, and event setup methods
- Customizable result processing
- Usage examples in comments

### 4. `php artisan laraflowai:chat`

Starts an interactive chat session for testing and development.

#### Usage

```bash
# Basic chat with default agent
php artisan laraflowai:chat

# Chat with specific agent
php artisan laraflowai:chat --agent=WriterAgent

# Chat with crew
php artisan laraflowai:chat --crew=ContentCrew

# Chat with flow
php artisan laraflowai:chat --flow=PublishingFlow

# With specific provider and model
php artisan laraflowai:chat --provider=openai --model=gpt-4

# With streaming enabled
php artisan laraflowai:chat --stream

# With memory disabled
php artisan laraflowai:chat --memory=false
```

#### Options

- `--agent`: Use a specific agent class
- `--crew`: Use a specific crew class
- `--flow`: Use a specific flow class
- `--provider`: Set the LLM provider (openai, anthropic, etc.)
- `--model`: Set the specific model to use
- `--memory`: Enable memory (true/false)
- `--stream`: Enable streaming responses

#### Interactive Commands

Once in the chat session, you can use these commands:

- `help` - Show available commands
- `clear` - Clear conversation history
- `status` - Show current AI configuration
- `exit`/`quit`/`bye` - End the chat session

#### Example Chat Session

```
🤖 Welcome to LaraFlowAI Interactive Chat!
Type your messages and press Enter to chat with the AI.
Type "exit", "quit", or "bye" to end the session.
Type "help" for available commands.

Using agent: WriterAgent

You: Write a blog post about Laravel
AI: I'll help you write a comprehensive blog post about Laravel. Let me create an engaging and informative piece that covers the key aspects of this popular PHP framework...

You: Make it more technical
AI: Certainly! Let me revise the blog post to include more technical details about Laravel's architecture, features, and implementation...

You: status
Current Configuration:
  Provider: openai
  Model: gpt-4
  Memory: enabled
  Streaming: disabled
  Agent: WriterAgent

You: exit
Goodbye! 👋
```

## Quick Start Guide

### 1. Create Your First Agent

```bash
php artisan laraflowai:make:agent BlogWriter --role=blog-writer --goal="Create engaging blog content" --tools=http
```

### 2. Test Your Agent

```bash
php artisan laraflowai:chat --agent=BlogWriter
```

### 3. Create a Crew

```bash
php artisan laraflowai:make:crew ContentTeam --agents=BlogWriter,ResearcherAgent --tasks="Research topics,Write content,Review content"
```

### 4. Test Your Crew

```bash
php artisan laraflowai:chat --crew=ContentTeam
```

### 5. Create a Flow

```bash
php artisan laraflowai:make:flow PublishingWorkflow --steps="Draft,Review,Edit,Publish" --events="content_ready,content_published"
```

### 6. Test Your Flow

```bash
php artisan laraflowai:chat --flow=PublishingWorkflow
```

## Best Practices

### Agent Development

1. **Clear Roles**: Define specific, focused roles for your agents
2. **Meaningful Goals**: Set clear, achievable goals
3. **Appropriate Tools**: Only include tools that the agent actually needs
4. **Memory Usage**: Enable memory for agents that need context

### Crew Coordination

1. **Complementary Agents**: Choose agents with complementary skills
2. **Clear Tasks**: Define specific, actionable tasks
3. **Execution Strategy**: Choose between sequential and parallel execution based on dependencies
4. **Result Processing**: Implement custom result processing when needed

### Flow Design

1. **Logical Steps**: Design steps that follow a logical sequence
2. **Meaningful Conditions**: Use conditions to control flow execution
3. **Event Handling**: Implement proper event handlers for workflow events
4. **Error Handling**: Consider error scenarios and recovery strategies

### Testing and Development

1. **Interactive Testing**: Use the chat command for rapid prototyping
2. **Provider Testing**: Test with different providers and models
3. **Memory Testing**: Test both with and without memory enabled
4. **Streaming Testing**: Test both regular and streaming responses

## Troubleshooting

### Common Issues

1. **Class Not Found**: Make sure to run the make commands before using the chat command
2. **Provider Errors**: Check your API keys and provider configuration
3. **Memory Issues**: Ensure database migrations are run for memory functionality
4. **Tool Errors**: Verify that required tools are properly configured

### Getting Help

- Use `php artisan laraflowai:chat --help` for command options
- Use `help` command within the chat session
- Check the main documentation for detailed usage examples
- Review the generated code comments for implementation guidance

## Integration with Laravel

The generated classes follow Laravel conventions and can be easily integrated into your Laravel application:

1. **Service Container**: Classes are automatically registered in Laravel's service container
2. **Dependency Injection**: Use dependency injection for better testability
3. **Configuration**: Use Laravel's configuration system for settings
4. **Events**: Leverage Laravel's event system for workflow events
5. **Jobs**: Use Laravel's job system for background processing

This scaffolding system makes LaraFlowAI more accessible to Laravel developers by providing a familiar Laravel-way approach to AI development.
