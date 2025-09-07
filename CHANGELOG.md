# Changelog

All notable changes to LaraFlowAI will be documented in this file.

## [alpha3] - 2025-09-07

### Added
- **Universal MCP Client**: Complete Model Context Protocol (MCP) support with JSON-RPC 2.0
- **New AI Providers**: Added support for Grok, Gemini, DeepSeek, and Groq providers
- **MCP Tool Integration**: Built-in MCP tool support for external service integration
- **Fluent Interface**: Enhanced method chaining for agents, crews, and flows
- **Batch Operations**: New batch methods for agent and task management
- **Laravel 12 Support**: Full compatibility with Laravel 12.x
- **Enhanced Error Handling**: Improved error messages and exception handling
- **MCP Documentation**: Comprehensive MCP client documentation and examples

### Enhanced
- **Provider System**: Expanded provider ecosystem with 8+ AI providers
- **Tool System**: Enhanced tool architecture with MCP integration
- **Memory Management**: Improved context handling and memory operations
- **Code Quality**: Enhanced type hints and method documentation
- **Example Scripts**: Updated all examples with fluent interface patterns

### Fixed
- **Composer Dependencies**: Updated to support Laravel 12.x
- **Token Usage Tracking**: Removed cost tracking from token usage metrics
- **Method Signatures**: Improved method consistency across the codebase
- **Error Messages**: More descriptive error messages throughout

### Technical Details

#### MCP (Model Context Protocol) Support
- **Universal MCP Client**: Full JSON-RPC 2.0 implementation
- **Server Management**: Dynamic server configuration and health monitoring
- **Tool Integration**: Seamless integration with external MCP tools
- **Resource Management**: Support for MCP resources and prompts
- **Caching**: Intelligent caching for tools, resources, and samples
- **Error Handling**: Specific MCP exception classes and error codes

#### New AI Providers
- **Grok Provider**: X.AI Grok models with enhanced reasoning capabilities
- **Gemini Provider**: Google Gemini models with multimodal support
- **DeepSeek Provider**: DeepSeek models with coding specialization
- **Groq Provider**: Groq inference engine for fast model execution
- **Model Capabilities**: Detailed capability reporting for each provider

#### Enhanced Architecture
- **Fluent Interface**: Method chaining for improved developer experience
- **Batch Operations**: Efficient bulk operations for agents and tasks
- **Context Management**: Enhanced context building and memory integration
- **Type Safety**: Improved type hints and return type declarations
- **Documentation**: Comprehensive PHPDoc coverage for all methods

## [alpha2] - 2025-09-06

### Added
- **Comprehensive PHPDoc Documentation**: Complete documentation for all classes, methods, and properties
- **Code Quality Improvements**: Enhanced type hints and documentation standards
- **Removed Unused Middleware**: Cleaned up AuthMiddleware and RateLimitMiddleware that were not needed

### Fixed
- **Documentation Coverage**: All source files now have proper PHPDoc documentation
- **Type Safety**: Improved type hints throughout the codebase
- **Code Organization**: Removed unnecessary HTTP middleware files

## [0.1.0] - 2025-09-05

### Added

#### Core Features
- **Multi-Agent System**: Complete agent implementation with roles, goals, and tools
- **Crew Management**: Team-based agent coordination with sequential and parallel execution
- **Flow Control**: Complex workflow management with conditional execution
- **Memory System**: Short-term and long-term memory persistence with database storage
- **LLM Factory**: Dynamic provider switching and custom provider registration
- **Tool System**: Built-in tools for HTTP, Database, Filesystem, and MCP operations
- **MCP Integration**: Universal Model Context Protocol client for external service integration

#### Providers
- **OpenAI Provider**: Full support for GPT models with streaming
- **Anthropic Provider**: Claude model support
- **Ollama Provider**: Local model support with streaming
- **Grok Provider**: X.AI Grok models with enhanced reasoning
- **Gemini Provider**: Google Gemini models with multimodal support
- **DeepSeek Provider**: DeepSeek models with coding specialization
- **Groq Provider**: Groq inference engine for fast execution
- **Custom Provider**: Easy registration of custom providers

#### Memory & Persistence
- **Database Storage**: MySQL/PostgreSQL support for memory persistence
- **Cache Integration**: Redis/Memcached support for performance
- **Search Functionality**: Full-text search across stored memories
- **Automatic Cleanup**: Expired data cleanup with configurable retention

#### Queue Integration
- **Async Execution**: Background processing for crews and flows
- **Job Management**: Laravel job system integration
- **Event Dispatching**: Real-time event notifications

#### Observability
- **Token Tracking**: Comprehensive usage and cost monitoring
- **Monitoring**: Command-line monitoring and statistics
- **Logging**: Detailed logging with configurable levels
- **Metrics**: Usage statistics and performance monitoring

#### Security & Validation
- **Input Validation**: Comprehensive validation for all inputs
- **Rate Limiting**: Built-in rate limiting middleware
- **Authentication**: Secure access control
- **Error Handling**: Robust error handling and reporting

#### Developer Experience
- **Simple Templates**: Built-in prompt templating system
- **Artisan Commands**: Management commands for cleanup and monitoring
- **Comprehensive Tests**: Full test suite with unit and integration tests
- **Documentation**: Complete API documentation and examples

#### Configuration
- **Environment Variables**: Easy configuration via .env
- **Service Provider**: Laravel-style service registration
- **Facade**: Clean API through FlowAI facade
- **Middleware**: Security and rate limiting middleware

### Technical Details

#### Architecture
- **PSR-4 Autoloading**: Standard PHP autoloading
- **Laravel Integration**: Full Laravel framework integration
- **Service Container**: Dependency injection support
- **Event System**: Laravel event system integration

#### Database
- **Migrations**: Database schema management
- **Indexes**: Optimized database queries
- **Relationships**: Proper foreign key relationships
- **Cleanup**: Automated data cleanup

#### Performance
- **Caching**: Multi-level caching strategy
- **Queue Processing**: Background job processing
- **Memory Management**: Efficient memory usage
- **Database Optimization**: Optimized queries and indexes

#### Testing
- **Unit Tests**: Comprehensive unit test coverage
- **Integration Tests**: End-to-end testing
- **Mocking**: Proper mocking for external dependencies
- **Test Helpers**: Reusable test utilities

### Breaking Changes

None - This is the initial release.

### Dependencies

- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0
- Guzzle HTTP ^7.0
- Symfony Expression Language ^6.0|^7.0

### Installation

```bash
composer require laraflowai/laraflowai
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"
php artisan migrate
```

### Configuration

Add to your `.env` file:

```env
# Core AI Providers
OPENAI_API_KEY=your_openai_api_key
ANTHROPIC_API_KEY=your_anthropic_api_key
OLLAMA_HOST=http://localhost:11434

# New AI Providers
GROK_API_KEY=your_grok_api_key
GEMINI_API_KEY=your_gemini_api_key
DEEPSEEK_API_KEY=your_deepseek_api_key
GROQ_API_KEY=your_groq_api_key

# Configuration
LARAFLOWAI_DEFAULT_PROVIDER=openai

# MCP Configuration (Optional)
MCP_RETRY_ATTEMPTS=3
MCP_CACHE_TOOLS_TTL=3600
MCP_LOGGING_ENABLED=true
```

### Usage

```php
use LaraFlowAI\Facades\FlowAI;

// Create an agent with fluent interface
$agent = FlowAI::agent('Content Writer', 'Create engaging content')
    ->addTool(new HttpTool())
    ->addTool(new DatabaseTool())
    ->setContext(['style' => 'professional']);

// Create a task
$task = FlowAI::task('Write a blog post about Laravel 12');

// Handle the task
$response = $agent->handle($task);
echo $response->getContent();

// MCP Integration Example
$mcpClient = new \LaraFlowAI\MCP\MCPClient($config);
$tools = $mcpClient->getTools('claude-mcp');
$response = $mcpClient->callTool('claude-mcp', 'search_web', ['query' => 'Laravel 12']);
```

### Documentation

- **README.md**: Quick start guide
- **docs/API.md**: Complete API documentation
- **docs/UNIVERSAL_MCP_CLIENT.md**: MCP client documentation
- **examples/**: Usage examples including MCP integration
- **tests/**: Test examples

### Support

- **GitHub Issues**: Bug reports and feature requests
- **Documentation**: Comprehensive guides and examples
- **Tests**: Verify installation and usage
- **Logs**: Debug information in Laravel logs

### License

MIT License - see LICENSE file for details.

### Contributing

Contributions are welcome! Please see CONTRIBUTING.md for guidelines.

### Roadmap

#### Version 1.1.0
- [x] MCP (Model Context Protocol) integration
- [x] Additional AI providers (Grok, Gemini, DeepSeek, Groq)
- [x] Fluent interface improvements
- [x] Laravel 12 compatibility
- [ ] Vector database support for RAG
- [ ] Advanced prompt templating
- [ ] Custom tool builder
- [ ] Performance optimizations

#### Version 1.2.0
- [ ] Multi-tenant support
- [ ] Advanced analytics
- [ ] Custom dashboard themes
- [ ] API versioning
- [ ] Enhanced MCP server management

#### Version 2.0.0
- [ ] Graph-based workflows
- [ ] Advanced AI models
- [ ] Enterprise features
- [ ] Cloud deployment tools
- [ ] MCP marketplace integration
