<?php

namespace LaraFlowAI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFlowCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'laraflowai:make:flow 
                            {name : The name of the flow class}
                            {--steps= : Comma-separated list of step descriptions}
                            {--conditions= : Comma-separated list of condition descriptions}
                            {--memory= : Enable memory for the flow (true/false)}
                            {--events= : Comma-separated list of event names}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new LaraFlowAI Flow class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $steps = $this->option('steps') ? explode(',', $this->option('steps')) : [];
        $conditions = $this->option('conditions') ? explode(',', $this->option('conditions')) : [];
        $memory = $this->option('memory') ?: $this->confirm('Enable memory for this flow?', true);
        $events = $this->option('events') ? explode(',', $this->option('events')) : [];
        $force = $this->option('force');

        $className = Str::studly($name);
        $fileName = $className . '.php';
        $directory = app_path('LaraFlowAI/Flows');
        $filePath = $directory . '/' . $fileName;

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Check if file already exists
        if (File::exists($filePath) && !$force) {
            $this->error("Flow class {$className} already exists. Use --force to overwrite.");
            return Command::FAILURE;
        }

        // Generate the flow class
        $stub = $this->getStub();
        $content = $this->replaceStubVariables($stub, [
            'className' => $className,
            'steps' => $steps,
            'conditions' => $conditions,
            'memory' => $memory ? 'true' : 'false',
            'events' => $events,
            'stepsArray' => $this->generateStepsArray($steps),
            'conditionsArray' => $this->generateConditionsArray($conditions),
            'eventsArray' => $this->generateEventsArray($events),
            'stepsMethod' => $this->generateStepsMethod($steps),
            'conditionsMethod' => $this->generateConditionsMethod($conditions),
            'eventsMethod' => $this->generateEventsMethod($events),
        ]);

        File::put($filePath, $content);

        $this->info("Flow class {$className} created successfully!");
        $this->line("File: {$filePath}");
        
        $this->newLine();
        $this->comment('Usage example:');
        $this->line("use App\\LaraFlowAI\\Flows\\{$className};");
        $this->line("use LaraFlowAI\Facades\\FlowAI;");
        $this->newLine();
        $this->line("\$flow = new {$className}();");
        $this->line("\$result = FlowAI::flow(\$flow)->execute();");

        return Command::SUCCESS;
    }

    /**
     * Get the stub content for the flow class.
     */
    protected function getStub(): string
    {
        return '<?php

namespace App\LaraFlowAI\Flows;

use LaraFlowAI\Flow;
use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;
use LaraFlowAI\Facades\FlowAI;

/**
 * {{className}} Flow
 * 
 * A workflow that executes steps in sequence with conditions and events.
 * 
 * @package App\LaraFlowAI\Flows
 */
class {{className}} extends Flow
{
    /**
     * Create a new {{className}} flow instance.
     */
    public function __construct()
    {
        parent::__construct(
            memory: FlowAI::memory()
        );

        $this->setupSteps();
        $this->setupConditions();
        $this->setupEvents();
    }

    /**
     * Setup steps for this flow.
     */
    protected function setupSteps(): void
    {
        {{stepsMethod}}
    }

    /**
     * Setup conditions for this flow.
     */
    protected function setupConditions(): void
    {
        {{conditionsMethod}}
    }

    /**
     * Setup events for this flow.
     */
    protected function setupEvents(): void
    {
        {{eventsMethod}}
    }

    /**
     * Get steps for this flow.
     * 
     * @return array<int, FlowStep>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get conditions for this flow.
     * 
     * @return array<int, FlowCondition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Get events for this flow.
     * 
     * @return array<string, callable>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * Process the flow\'s result before returning.
     * 
     * @param \LaraFlowAI\FlowResult $result
     * @return \LaraFlowAI\FlowResult
     */
    protected function processResult(\LaraFlowAI\FlowResult $result): \LaraFlowAI\FlowResult
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
     * Generate steps array for the flow.
     */
    protected function generateStepsArray(array $steps): string
    {
        if (empty($steps)) {
            return '// No steps configured';
        }

        $stepsArray = [];
        foreach ($steps as $index => $step) {
            $stepsArray[] = "            new FlowStep('{$step}', 'custom'),";
        }

        return implode("\n", $stepsArray);
    }

    /**
     * Generate conditions array for the flow.
     */
    protected function generateConditionsArray(array $conditions): string
    {
        if (empty($conditions)) {
            return '// No conditions configured';
        }

        $conditionsArray = [];
        foreach ($conditions as $condition) {
            $conditionsArray[] = "            new FlowCondition('{$condition}'),";
        }

        return implode("\n", $conditionsArray);
    }

    /**
     * Generate events array for the flow.
     */
    protected function generateEventsArray(array $events): string
    {
        if (empty($events)) {
            return '// No events configured';
        }

        $eventsArray = [];
        foreach ($events as $event) {
            $eventsArray[] = "            '{$event}' => function() { /* Handle {$event} event */ },";
        }

        return implode("\n", $eventsArray);
    }

    /**
     * Generate steps setup method.
     */
    protected function generateStepsMethod(array $steps): string
    {
        if (empty($steps)) {
            return '// No steps to setup';
        }

        $method = [];
        foreach ($steps as $step) {
            $method[] = "        \$this->addStep(new FlowStep('{$step}', 'custom'));";
        }

        return implode("\n", $method);
    }

    /**
     * Generate conditions setup method.
     */
    protected function generateConditionsMethod(array $conditions): string
    {
        if (empty($conditions)) {
            return '// No conditions to setup';
        }

        $method = [];
        foreach ($conditions as $condition) {
            $method[] = "        \$this->addCondition(new FlowCondition('{$condition}'));";
        }

        return implode("\n", $method);
    }

    /**
     * Generate events setup method.
     */
    protected function generateEventsMethod(array $events): string
    {
        if (empty($events)) {
            return '// No events to setup';
        }

        $method = [];
        foreach ($events as $event) {
            $method[] = "        \$this->on('{$event}', function() { /* Handle {$event} event */ });";
        }

        return implode("\n", $method);
    }
}
