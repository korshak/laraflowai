<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrewCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:make:crew 
                            {name : The name of the crew class}
                            {--agents= : Comma-separated list of agent classes to include}
                            {--tasks= : Comma-separated list of task descriptions}
                            {--memory= : Enable memory for the crew (true/false)}
                            {--parallel= : Enable parallel execution (true/false)}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new LaraFlowAI Crew class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $agents = $this->option('agents') ? explode(',', $this->option('agents')) : [];
        $tasks = $this->option('tasks') ? explode(',', $this->option('tasks')) : [];
        $memory = $this->option('memory') ?: $this->confirm('Enable memory for this crew?', true);
        $parallel = $this->option('parallel') ?: $this->confirm('Enable parallel execution?', false);
        $force = $this->option('force');

        $className = Str::studly($name);
        $fileName = $className . '.php';
        $directory = app_path('LaraFlowAI/Crews');
        $filePath = $directory . '/' . $fileName;

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file already exists
        if (File::exists($filePath) && !$force) {
            $this->error("Crew class {$className} already exists. Use --force to overwrite.");
            return Command::FAILURE;
        }

        // Generate the crew class
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub, [
            'className' => $className,
            'agents' => $agents,
            'tasks' => $tasks,
            'memory' => $memory ? 'true' : 'false',
            'parallel' => $parallel ? 'true' : 'false',
            'agentsArray' => $this->generateAgentsArray($agents),
            'tasksArray' => $this->generateTasksArray($tasks),
            'agentsMethod' => $this->generateAgentsMethod($agents),
            'tasksMethod' => $this->generateTasksMethod($tasks),
        ]);

        File::put($filePath, $content);

        $this->info("Crew class {$className} created successfully!");
        $this->line("File: {$filePath}");
        
        $this->newLine();
        $this->comment('Usage example:');
        $this->line("use App\\LaraFlowAI\\Crews\\{$className};");
        $this->line("use LaraFlowAI\Facades\\FlowAI;");
        $this->newLine();
        $this->line("\$crew = new {$className}();");
        $this->line("\$result = FlowAI::crew(\$crew)->execute();");

        return Command::SUCCESS;
    }

    /**
     * Get the stub content for the crew class.
     */
    protected function getStub(): string
    {
        return '<?php

namespace App\LaraFlowAI\Crews;

use LaraFlowAI\Crew;
use LaraFlowAI\Agent;
use LaraFlowAI\Task;

/**
 * {{className}} Crew
 * 
 * A crew that coordinates multiple agents to work together on tasks.
 * 
 * @package App\LaraFlowAI\Crews
 */
class {{className}} extends Crew
{
    /**
     * Create a new {{className}} crew instance.
     */
    public function __construct()
    {
        parent::__construct(
            memory: {{memory}},
            parallel: {{parallel}}
        );

        $this->setupAgents();
        $this->setupTasks();
    }

    /**
     * Setup agents for this crew.
     */
    protected function setupAgents(): void
    {
        {{agentsMethod}}
    }

    /**
     * Setup tasks for this crew.
     */
    protected function setupTasks(): void
    {
        {{tasksMethod}}
    }

    /**
     * Get agents for this crew.
     * 
     * @return array<string, Agent>
     */
    public function getAgents(): array
    {
        return [
            {{agentsArray}}
        ];
    }

    /**
     * Get tasks for this crew.
     * 
     * @return array<int, Task>
     */
    public function getTasks(): array
    {
        return [
            {{tasksArray}}
        ];
    }

    /**
     * Process the crew\'s result before returning.
     * 
     * @param \LaraFlowAI\CrewResult $result
     * @return \LaraFlowAI\CrewResult
     */
    protected function processResult(\LaraFlowAI\CrewResult $result): \LaraFlowAI\CrewResult
    {
        // Add any custom result processing logic here
        return $result;
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
     * Generate agents array for the crew.
     */
    protected function generateAgentsArray(array $agents): string
    {
        if (empty($agents)) {
            return '// No agents configured';
        }

        $agentsArray = [];
        foreach ($agents as $agent) {
            $agentClass = Str::studly($agent);
            $agentsArray[] = "            '{$agent}' => new \\App\\LaraFlowAI\\Agents\\{$agentClass}(),";
        }

        return implode("\n", $agentsArray);
    }

    /**
     * Generate tasks array for the crew.
     */
    protected function generateTasksArray(array $tasks): string
    {
        if (empty($tasks)) {
            return '// No tasks configured';
        }

        $tasksArray = [];
        foreach ($tasks as $index => $task) {
            $tasksArray[] = "            new Task('{$task}'),";
        }

        return implode("\n", $tasksArray);
    }

    /**
     * Generate agents setup method.
     */
    protected function generateAgentsMethod(array $agents): string
    {
        if (empty($agents)) {
            return '// No agents to setup';
        }

        $method = [];
        foreach ($agents as $agent) {
            $agentClass = Str::studly($agent);
            $method[] = "        \$this->addAgent('{$agent}', new \\App\\LaraFlowAI\\Agents\\{$agentClass}());";
        }

        return implode("\n", $method);
    }

    /**
     * Generate tasks setup method.
     */
    protected function generateTasksMethod(array $tasks): string
    {
        if (empty($tasks)) {
            return '// No tasks to setup';
        }

        $method = [];
        foreach ($tasks as $task) {
            $method[] = "        \$this->addTask(new Task('{$task}'));";
        }

        return implode("\n", $method);
    }
}
