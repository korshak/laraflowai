<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:make:agent 
                            {name : The name of the agent class}
                            {--role= : The role of the agent (e.g., writer, researcher)}
                            {--goal= : The goal of the agent}
                            {--tools= : Comma-separated list of tools to include}
                            {--memory= : Enable memory for the agent (true/false)}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new LaraFlowAI Agent class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $role = $this->option('role') ?: $this->ask('What is the agent\'s role?', 'assistant');
        $goal = $this->option('goal') ?: $this->ask('What is the agent\'s goal?', 'Help users with their tasks');
        $tools = $this->option('tools') ? explode(',', $this->option('tools')) : [];
        $memory = $this->option('memory') ?: $this->confirm('Enable memory for this agent?', true);
        $force = $this->option('force');

        $className = Str::studly($name);
        $fileName = $className . '.php';
        $directory = app_path('LaraFlowAI/Agents');
        $filePath = $directory . '/' . $fileName;

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file already exists
        if (File::exists($filePath) && !$force) {
            $this->error("Agent class {$className} already exists. Use --force to overwrite.");
            return Command::FAILURE;
        }

        // Generate the agent class
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub, [
            'className' => $className,
            'role' => $role,
            'goal' => $goal,
            'tools' => $tools,
            'memory' => $memory ? 'true' : 'false',
            'toolsArray' => $this->generateToolsArray($tools),
            'toolsMethod' => $this->generateToolsMethod($tools),
        ]);

        File::put($filePath, $content);

        $this->info("Agent class {$className} created successfully!");
        $this->line("File: {$filePath}");
        
        $this->newLine();
        $this->comment('Usage example:');
        $this->line("use App\\LaraFlowAI\\Agents\\{$className};");
        $this->line("use LaraFlowAI\Facades\\FlowAI;");
        $this->newLine();
        $this->line("\$agent = new {$className}();");
        $this->line("\$response = FlowAI::agent(\$agent)->execute('Your task here');");

        return Command::SUCCESS;
    }

    /**
     * Get the stub content for the agent class.
     */
    protected function getStub(): string
    {
        return '<?php

namespace App\LaraFlowAI\Agents;

use LaraFlowAI\Agent;
use LaraFlowAI\Contracts\ToolContract;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;
use LaraFlowAI\Facades\FlowAI;

/**
 * {{className}} Agent
 * 
 * Role: {{role}}
 * Goal: {{goal}}
 * 
 * @package App\LaraFlowAI\Agents
 */
class {{className}} extends Agent
{
    /**
     * Create a new {{className}} agent instance.
     */
    public function __construct()
    {
        parent::__construct(
            role: \'{{role}}\',
            goal: \'{{goal}}\',
            provider: FlowAI::llm(\'ollama\'),
            memory: FlowAI::memory()
        );

        $this->setupTools();
    }

    /**
     * Setup tools for this agent.
     */
    protected function setupTools(): void
    {
        {{toolsMethod}}
    }

    /**
     * Get available tools for this agent.
     * 
     * @return array<string, ToolContract>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Process the agent\'s response before returning.
     * 
     * @param string $response
     * @return string
     */
    protected function processResponse(string $response): string
    {
        // Add any custom response processing logic here
        return $response;
    }
}';
    }

    /**
     * Replace variables in the stub content.
     */
    protected function replaceStubVariables(string $stub, array $variables): string
    {
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $stub = str_replace('{{' . $key . '}}', (string) $value, $stub);
        }

        return $stub;
    }

    /**
     * Generate tools array for the agent.
     */
    protected function generateToolsArray(array $tools): string
    {
        if (empty($tools)) {
            return '// No tools configured';
        }

        $toolsArray = [];
        foreach ($tools as $tool) {
            $toolClass = $this->getToolClass($tool);
            $toolsArray[] = "            '{$tool}' => new {$toolClass}(),";
        }

        return implode("\n", $toolsArray);
    }

    /**
     * Generate tools setup method.
     */
    protected function generateToolsMethod(array $tools): string
    {
        if (empty($tools)) {
            return '// No tools to setup';
        }

        $method = [];
        foreach ($tools as $tool) {
            $toolClass = $this->getToolClass($tool);
            $method[] = "        \$this->addTool(new {$toolClass}());";
        }

        return implode("\n", $method);
    }

    /**
     * Get the tool class for a given tool name.
     */
    protected function getToolClass(string $tool): string
    {
        return match (strtolower($tool)) {
            'http' => 'HttpTool',
            'database', 'db' => 'DatabaseTool',
            'filesystem', 'fs' => 'FilesystemTool',
            default => 'HttpTool', // Default to HttpTool
        };
    }
}
