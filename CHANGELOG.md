# Changelog

All notable changes to LaraFlowAI will be documented in this file.

## [Unreleased]

### Added
- **Streaming Responses**: Real-time token-by-token output for improved UX
- **StreamingResponse Class**: Dedicated class for handling streaming responses
- **Agent Streaming**: `->stream()` method for agents with real-time output
- **Crew Streaming**: `->stream()` method for crew execution with streaming
- **Task Streaming Configuration**: `->stream()` method for task-level streaming setup
- **Livewire Integration**: Complete examples for Livewire streaming components
- **WebSocket Support**: Pusher integration for real-time streaming
- **Server-Sent Events**: SSE support for streaming responses
- **Buffer Management**: Token buffering and caching for optimization
- **Streaming Configuration**: Comprehensive config options for streaming behavior

### Enhanced
- **Provider Streaming**: All providers now support native streaming APIs
- **Memory System**: Enhanced memory storage for streaming responses
- **Error Handling**: Improved error handling for streaming operations
- **Performance**: Optimized streaming with buffering and caching
- **Documentation**: Complete streaming documentation with examples

## [0.1.0-alpha3]

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

## [0.1.0-alpha2]

### Added
- **Comprehensive PHPDoc Documentation**: Complete documentation for all classes, methods, and properties
- **Code Quality Improvements**: Enhanced type hints and documentation standards
- **Removed Unused Middleware**: Cleaned up AuthMiddleware and RateLimitMiddleware that were not needed

### Fixed
- **Documentation Coverage**: All source files now have proper PHPDoc documentation
- **Type Safety**: Improved type hints throughout the codebase
- **Code Organization**: Removed unnecessary HTTP middleware files

## [0.1.0-alpha1]

### Added
- **Multi-Agent System**: Complete agent implementation with roles, goals, and tools
- **Crew Management**: Team-based agent coordination with sequential and parallel execution
- **Flow Control**: Complex workflow management with conditional execution
- **Memory System**: Short-term and long-term memory persistence with database storage
- **LLM Factory**: Dynamic provider switching and custom provider registration
- **Tool System**: Built-in tools for HTTP, Database, Filesystem, and MCP operations
- **MCP Integration**: Universal Model Context Protocol client for external service integration
- **AI Providers**: OpenAI, Anthropic, Ollama, Grok, Gemini, DeepSeek, and Groq support
- **Queue Integration**: Background processing for crews and flows
- **Artisan Commands**: Management commands for cleanup and monitoring
- **Comprehensive Tests**: Full test suite with unit and integration tests
- **Documentation**: Complete API documentation and examples

### Dependencies
- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0
- Guzzle HTTP ^7.0
- Symfony Expression Language ^6.0|^7.0
