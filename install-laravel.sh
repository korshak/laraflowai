#!/bin/bash

# LaraFlowAI Laravel Installation Script
# This script helps you install and configure LaraFlowAI in your Laravel project

echo "🚀 LaraFlowAI Laravel Installation Script"
echo "=========================================="
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project."
    echo "   Please run this script from your Laravel project root directory."
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Install the package
echo "📦 Installing LaraFlowAI package..."
composer require laraflowai/laraflowai

if [ $? -ne 0 ]; then
    echo "❌ Failed to install LaraFlowAI package"
    exit 1
fi

echo "✅ Package installed successfully"
echo ""

# Publish configuration
echo "⚙️  Publishing configuration and migrations..."
php artisan vendor:publish --provider="LaraFlowAI\LaraFlowAIServiceProvider"

if [ $? -ne 0 ]; then
    echo "❌ Failed to publish configuration"
    exit 1
fi

echo "✅ Configuration published successfully"
echo ""

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate

if [ $? -ne 0 ]; then
    echo "❌ Failed to run migrations"
    exit 1
fi

echo "✅ Migrations completed successfully"
echo ""

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "⚠️  Warning: .env file not found. Please create one from .env.example"
    echo "   cp .env.example .env"
    echo "   php artisan key:generate"
    echo ""
fi

# Add environment variables
echo "🔧 Adding environment variables to .env file..."

# Check if variables already exist
if grep -q "LARAFLOWAI" .env 2>/dev/null; then
    echo "⚠️  LaraFlowAI environment variables already exist in .env"
else
    cat >> .env << 'EOF'

# LaraFlowAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
ANTHROPIC_API_KEY=your_anthropic_api_key_here
GROQ_API_KEY=your_groq_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here
OLLAMA_HOST=http://localhost:11434
LARAFLOWAI_DEFAULT_PROVIDER=openai
LARAFLOWAI_QUEUE_ENABLED=false
LARAFLOWAI_LOGGING_ENABLED=true
LARAFLOWAI_MEMORY_CACHE_TTL=3600
EOF
    echo "✅ Environment variables added to .env"
fi

echo ""

# Test installation
echo "🧪 Testing installation..."
php artisan laraflowai:test-provider openai --model=gpt-3.5-turbo

if [ $? -eq 0 ]; then
    echo "✅ Installation test passed"
else
    echo "⚠️  Installation test failed - this is normal if you haven't set up API keys yet"
fi

echo ""

# Show next steps
echo "🎉 Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Add your API keys to the .env file:"
echo "   - OPENAI_API_KEY=your_actual_key_here"
echo "   - GROQ_API_KEY=your_actual_key_here (optional)"
echo "   - GEMINI_API_KEY=your_actual_key_here (optional)"
echo "   - ANTHROPIC_API_KEY=your_actual_key_here (optional)"
echo ""
echo "2. Test the installation:"
echo "   php artisan laraflowai:test-provider openai"
echo ""
echo "3. View usage statistics:"
echo "   php artisan laraflowai:stats"
echo ""
echo "4. Read the documentation:"
echo "   - Laravel Quick Start: docs/LARAVEL_QUICKSTART.md"
echo "   - Laravel Usage Guide: docs/LARAVEL_USAGE.md"
echo "   - Examples: examples/laravel-integration.php"
echo ""
echo "5. Start building AI-powered features! 🚀"
echo ""

# Show available commands
echo "Available Artisan commands:"
echo "  php artisan laraflowai:stats --days=30"
echo "  php artisan laraflowai:cleanup-memory --days=90"
echo "  php artisan laraflowai:cleanup-tokens --days=90"
echo "  php artisan laraflowai:test-provider openai --model=gpt-4"
echo ""

echo "Happy coding! 🎯"
