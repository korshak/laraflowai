<?php

/**
 * LaraFlowAI Laravel Integration Examples
 * 
 * This file demonstrates real-world Laravel integration patterns
 * for using LaraFlowAI in Laravel applications.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use LaraFlowAI\Facades\FlowAI;
use LaraFlowAI\Tools\HttpTool;
use LaraFlowAI\Tools\DatabaseTool;
use LaraFlowAI\Tools\FilesystemTool;

echo "=== LaraFlowAI Laravel Integration Examples ===\n\n";

// Example 1: Content Management System
echo "1. Content Management System\n";
echo "============================\n";

class ContentManager
{
    public function generateArticle(string $topic, string $style = 'casual'): array
    {
        // Create specialized agents for content creation
        $researcher = FlowAI::agent('Researcher', 'Gather accurate information and facts');
        $writer = FlowAI::agent('Content Writer', 'Create engaging and well-structured articles');
        $editor = FlowAI::agent('Editor', 'Review and improve content quality');
        $seo = FlowAI::agent('SEO Specialist', 'Optimize content for search engines');
        
        // Add tools to researcher
        $researcher->addTool(new HttpTool())
            ->addTool(new DatabaseTool());
        
        // Create tasks
        $tasks = [
            FlowAI::task("Research comprehensive information about {$topic}")
                ->setToolInput('http', [
                    'url' => 'https://laravel.com/docs',
                    'method' => 'GET'
                ]),
            FlowAI::task("Write a {$style} article about {$topic} based on research"),
            FlowAI::task("Edit and improve the article for clarity and flow"),
            FlowAI::task("Optimize the article for SEO with relevant keywords"),
        ];
        
        // Create and execute crew
        $crew = FlowAI::crew()
            ->agents([$researcher, $writer, $editor, $seo])
            ->tasks($tasks);
        
        $result = $crew->execute();
        
        if ($result->isSuccess()) {
            return [
                'success' => true,
                'content' => $result->getResults(),
                'execution_time' => $result->getExecutionTime(),
                'word_count' => str_word_count($result->getResults()[0]['response']->getContent()),
            ];
        }
        
        return [
            'success' => false,
            'error' => $result->getErrorMessage(),
        ];
    }
}

$contentManager = new ContentManager();
$article = $contentManager->generateArticle('Laravel 11 Features', 'technical');
echo "Article generated: " . ($article['success'] ? 'Yes' : 'No') . "\n";
if ($article['success']) {
    echo "Word count: " . $article['word_count'] . "\n";
    echo "Execution time: " . $article['execution_time'] . " seconds\n";
}

// Example 2: User Support Chat System
echo "\n2. User Support Chat System\n";
echo "===========================\n";

class SupportChat
{
    public function handleUserMessage(string $userId, string $message): array
    {
        // Get user context from memory
        $userContext = FlowAI::memory()->recall("user_{$userId}_context") ?? [
            'name' => 'User',
            'previous_questions' => [],
            'preferences' => ['style' => 'friendly'],
        ];
        
        // Create support agent with context
        $agent = FlowAI::agent(
            role: 'Customer Support Agent',
            goal: 'Help users with their questions and provide excellent support',
            provider: 'openai'
        )->setContext($userContext);
        
        // Add tools for support
        $agent->addTool(new DatabaseTool())
            ->addTool(new HttpTool());
        
        // Create task with message
        $task = FlowAI::task($message)
            ->setToolInput('database', [
                'query' => 'SELECT * FROM support_articles WHERE title LIKE ?',
                'bindings' => ["%{$message}%"],
                'type' => 'select'
            ]);
        
        // Handle the task
        $response = $agent->handle($task);
        
        // Update user context
        $userContext['previous_questions'][] = [
            'question' => $message,
            'answer' => $response->getContent(),
            'timestamp' => now()->toISOString(),
        ];
        
        // Keep only last 10 questions
        $userContext['previous_questions'] = array_slice($userContext['previous_questions'], -10);
        
        FlowAI::memory()->store("user_{$userId}_context", $userContext);
        
        return [
            'response' => $response->getContent(),
            'context_updated' => true,
            'execution_time' => $response->getExecutionTime(),
        ];
    }
}

$supportChat = new SupportChat();
$chatResponse = $supportChat->handleUserMessage('user123', 'How do I install Laravel?');
echo "Support response: " . substr($chatResponse['response'], 0, 100) . "...\n";
echo "Context updated: " . ($chatResponse['context_updated'] ? 'Yes' : 'No') . "\n";

// Example 3: Data Analysis and Reporting
echo "\n3. Data Analysis and Reporting\n";
echo "==============================\n";

class DataAnalyst
{
    public function analyzeUserEngagement(): array
    {
        $analyst = FlowAI::agent(
            role: 'Data Analyst',
            goal: 'Analyze data and provide insights',
            provider: 'openai'
        )->addTool(new DatabaseTool());
        
        $task = FlowAI::task('Analyze user engagement data for the last 30 days')
            ->setToolInput('database', [
                'query' => 'SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as daily_users,
                    AVG(session_duration) as avg_session_duration
                    FROM user_activities 
                    WHERE created_at >= ? 
                    GROUP BY DATE(created_at) 
                    ORDER BY date DESC',
                'bindings' => [now()->subDays(30)],
                'type' => 'select'
            ]);
        
        $response = $analyst->handle($task);
        
        return [
            'analysis' => $response->getContent(),
            'execution_time' => $response->getExecutionTime(),
        ];
    }
}

$dataAnalyst = new DataAnalyst();
$analysis = $dataAnalyst->analyzeUserEngagement();
echo "Analysis completed: " . substr($analysis['analysis'], 0, 100) . "...\n";

// Example 4: Automated Workflow
echo "\n4. Automated Workflow\n";
echo "=====================\n";

use LaraFlowAI\FlowStep;
use LaraFlowAI\FlowCondition;

class AutomatedWorkflow
{
    public function processNewUser(string $userId, string $email): array
    {
        $flow = FlowAI::flow();
        
        // Step 1: Send welcome email
        $flow->addStep(FlowStep::custom('send_welcome', function($context) use ($email) {
            echo "Sending welcome email to: {$email}\n";
            return "Welcome email sent to {$email}";
        }));
        
        // Step 2: Create user profile
        $flow->addStep(FlowStep::custom('create_profile', function($context) use ($userId) {
            echo "Creating profile for user: {$userId}\n";
            return "Profile created for user {$userId}";
        }));
        
        // Step 3: Generate personalized content
        $flow->addStep(FlowStep::crew('personalize_content', $this->createPersonalizationCrew($userId)));
        
        // Step 4: Send follow-up after delay
        $flow->addStep(FlowStep::delay('follow_up_delay', 2))
            ->addStep(FlowStep::custom('send_follow_up', function($context) use ($email) {
                echo "Sending follow-up email to: {$email}\n";
                return "Follow-up email sent to {$email}";
            }));
        
        $result = $flow->run();
        
        return [
            'success' => $result->isSuccess(),
            'steps_completed' => $result->getSuccessfulStepCount(),
            'execution_time' => $result->getExecutionTime(),
            'results' => $result->getResults(),
        ];
    }
    
    private function createPersonalizationCrew(string $userId): \LaraFlowAI\Crew
    {
        $personalizer = FlowAI::agent('Content Personalizer', 'Create personalized content');
        $recommender = FlowAI::agent('Recommendation Engine', 'Suggest relevant content');
        
        $crew = FlowAI::crew()
            ->agents([$personalizer, $recommender])
            ->tasks([
                FlowAI::task("Create personalized welcome content for user {$userId}"),
                FlowAI::task("Recommend relevant articles and features for user {$userId}"),
            ]);
        
        return $crew;
    }
}

$workflow = new AutomatedWorkflow();
$workflowResult = $workflow->processNewUser('user456', 'user@example.com');
echo "Workflow completed: " . ($workflowResult['success'] ? 'Yes' : 'No') . "\n";
echo "Steps completed: " . $workflowResult['steps_completed'] . "\n";

// Example 5: API Controller Pattern
echo "\n5. API Controller Pattern\n";
echo "=========================\n";

class ApiController
{
    public function generateContent(Request $request): array
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'style' => 'nullable|string|in:casual,formal,technical',
            'provider' => 'nullable|string|in:openai,anthropic,ollama',
        ]);
        
        $agent = FlowAI::agent(
            role: 'Content Generator',
            goal: 'Create high-quality content based on user requirements',
            provider: $request->input('provider', 'openai')
        );
        
        $prompt = $request->input('style') 
            ? "Write a {$request->input('style')} article about: {$request->input('prompt')}"
            : $request->input('prompt');
        
        $task = FlowAI::task($prompt);
        $response = $agent->handle($task);
        
        // Log the request
        \Log::info('Content generated', [
            'user_id' => $request->user()?->id,
            'prompt' => $request->input('prompt'),
            'execution_time' => $response->getExecutionTime(),
        ]);
        
        return [
            'content' => $response->getContent(),
            'execution_time' => $response->getExecutionTime(),
            'provider' => $request->input('provider', 'openai'),
        ];
    }
    
    public function chat(Request $request): array
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'user_id' => 'required|integer',
        ]);
        
        $userContext = FlowAI::memory()->recall("user_{$request->user_id}_context") ?? [];
        
        $agent = FlowAI::agent('Chat Assistant', 'Provide helpful responses')
            ->setContext($userContext);
        
        $response = $agent->handle(FlowAI::task($request->message));
        
        // Update context
        $userContext['conversations'][] = [
            'message' => $request->message,
            'response' => $response->getContent(),
            'timestamp' => now(),
        ];
        
        FlowAI::memory()->store("user_{$request->user_id}_context", $userContext);
        
        return [
            'response' => $response->getContent(),
            'execution_time' => $response->getExecutionTime(),
        ];
    }
}

// Simulate API request
class Request
{
    public function input(string $key, $default = null)
    {
        $data = [
            'prompt' => 'Laravel best practices',
            'style' => 'technical',
            'provider' => 'openai',
            'message' => 'How do I optimize Laravel performance?',
            'user_id' => 123,
        ];
        
        return $data[$key] ?? $default;
    }
    
    public function user()
    {
        return (object) ['id' => 123];
    }
}

$apiController = new ApiController();
$contentResponse = $apiController->generateContent(new Request());
echo "API Content generated: " . substr($contentResponse['content'], 0, 50) . "...\n";

$chatResponse = $apiController->chat(new Request());
echo "API Chat response: " . substr($chatResponse['response'], 0, 50) . "...\n";

// Example 6: Queue Job Pattern
echo "\n6. Queue Job Pattern\n";
echo "===================\n";

class ContentGenerationJob
{
    public function __construct(
        private string $topic,
        private int $userId,
        private string $style = 'casual'
    ) {}
    
    public function handle(): void
    {
        $agent = FlowAI::agent('Content Writer', 'Create engaging content');
        $task = FlowAI::task("Write a {$this->style} article about {$this->topic}");
        
        $response = $agent->handle($task);
        
        // Store in database
        \DB::table('generated_content')->insert([
            'user_id' => $this->userId,
            'topic' => $this->topic,
            'content' => $response->getContent(),
            'style' => $this->style,
            'execution_time' => $response->getExecutionTime(),
            'created_at' => now(),
        ]);
        
        // Send notification
        \Log::info('Content generated for user', [
            'user_id' => $this->userId,
            'topic' => $this->topic,
            'execution_time' => $response->getExecutionTime(),
        ]);
    }
}

// Simulate job execution
$job = new ContentGenerationJob('Laravel Testing', 123, 'technical');
$job->handle();
echo "Job completed: Content generated and stored\n";

// Example 7: Event Handling
echo "\n7. Event Handling\n";
echo "=================\n";

class ContentEventHandler
{
    public function handleContentGenerated(\LaraFlowAI\Events\CrewExecuted $event): void
    {
        $result = $event->result;
        
        \Log::info('Content generation completed', [
            'execution_time' => $result->getExecutionTime(),
            'successful_tasks' => $result->getSuccessfulTaskCount(),
        ]);
        
        // Send notification, update database, etc.
        echo "Event handled: Content generation completed\n";
    }
}

// Simulate event
$event = new \LaraFlowAI\Events\CrewExecuted(
    new \LaraFlowAI\CrewResult([], 2.5, true)
);
$handler = new ContentEventHandler();
$handler->handleContentGenerated($event);

echo "\n=== All Laravel Integration Examples Completed ===\n";
echo "LaraFlowAI is ready for production Laravel applications! 🚀\n";
