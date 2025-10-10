#!/bin/bash
set -e

echo "🚀 Setting up Warden environment for ACP Magento 2..."

# Check if warden is installed
if ! command -v warden &> /dev/null; then
    echo "❌ Warden is not installed. Please install it first:"
    echo "   https://docs.warden.dev/installing.html"
    exit 1
fi

# Sign SSL certificate
echo "📜 Signing SSL certificate..."
warden sign-certificate acp-magento2.test

# Start Warden services
echo "🐳 Starting Warden services..."
warden svc up

# Start environment
echo "🏗️  Starting environment..."
warden env up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Enter shell and setup
echo "📦 Setting up Magento 2..."
warden shell << 'SHELL_EOF'
# Check if Magento is already installed
if [ ! -d "magento" ]; then
    echo "📥 Installing Magento 2.4.7..."
    composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition:2.4.7 magento --no-install
    
    cd magento
    
    # Add local module as repository
    composer config repositories.local path "../src"
    
    # Require the module
    composer require run-as-root/module-agentic-commerce-protocol:@dev --no-update
    
    # Install dependencies
    composer install
    
    # Install Magento
    bin/magento setup:install \
        --base-url=https://app.acp-magento2.test/ \
        --base-url-secure=https://app.acp-magento2.test/ \
        --db-host=db \
        --db-name=magento \
        --db-user=magento \
        --db-password=magento \
        --backend-frontname=admin \
        --admin-firstname=Admin \
        --admin-lastname=User \
        --admin-email=admin@example.com \
        --admin-user=admin \
        --admin-password=Admin123! \
        --language=en_US \
        --currency=USD \
        --timezone=America/Chicago \
        --use-rewrites=1 \
        --search-engine=elasticsearch7 \
        --elasticsearch-host=elasticsearch \
        --elasticsearch-port=9200 \
        --elasticsearch-index-prefix=magento2 \
        --session-save=redis \
        --session-save-redis-host=redis \
        --session-save-redis-port=6379 \
        --session-save-redis-db=2 \
        --session-save-redis-max-concurrency=20 \
        --cache-backend=redis \
        --cache-backend-redis-server=redis \
        --cache-backend-redis-db=0 \
        --cache-backend-redis-port=6379 \
        --page-cache=redis \
        --page-cache-redis-server=redis \
        --page-cache-redis-db=1 \
        --page-cache-redis-port=6379
    
    # Enable the module
    bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
    
    # Run setup upgrade
    bin/magento setup:upgrade
    
    # Compile DI
    bin/magento setup:di:compile
    
    # Deploy static content
    bin/magento setup:static-content:deploy -f
    
    # Reindex
    bin/magento indexer:reindex
    
    # Flush cache
    bin/magento cache:flush
    
    # Set developer mode
    bin/magento deploy:mode:set developer
    
    echo "✅ Magento 2 installed successfully!"
    echo ""
    echo "🎉 Access your store:"
    echo "   Frontend: https://app.acp-magento2.test/"
    echo "   Admin: https://app.acp-magento2.test/admin"
    echo "   Username: admin"
    echo "   Password: Admin123!"
    echo ""
    echo "📚 API Endpoints:"
    echo "   POST   /rest/V1/acp/checkout_sessions"
    echo "   GET    /rest/V1/acp/checkout_sessions/:id"
    echo "   POST   /rest/V1/acp/checkout_sessions/:id"
    echo "   POST   /rest/V1/acp/checkout_sessions/:id/complete"
    echo "   POST   /rest/V1/acp/checkout_sessions/:id/cancel"
else
    echo "ℹ️  Magento is already installed in the magento/ directory"
    cd magento
    bin/magento cache:flush
fi
SHELL_EOF

echo ""
echo "✅ Setup complete!"
echo ""
echo "🔧 Useful commands:"
echo "   warden shell          # Enter the container"
echo "   warden env down       # Stop the environment"
echo "   warden env up         # Start the environment"
echo "   warden db connect     # Connect to database"
