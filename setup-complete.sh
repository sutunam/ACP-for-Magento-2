#!/bin/bash
set -e

cd magento

echo "🔗 Configuring ACP module repository..."
composer config repositories.acp-module path "../src"

echo "📦 Installing ACP module..."
composer require run-as-root/module-agentic-commerce-protocol:@dev

echo "🏗️  Installing Magento..."
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
    --search-engine=opensearch \
    --opensearch-host=opensearch \
    --opensearch-port=9200 \
    --opensearch-index-prefix=magento2 \
    --opensearch-enable-auth=0 \
    --opensearch-timeout=15 \
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

echo "🔌 Enabling ACP module..."
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol

echo "⬆️  Running setup:upgrade..."
bin/magento setup:upgrade

echo "🔨 Compiling DI..."
bin/magento setup:di:compile

echo "🎨 Deploying static content..."
bin/magento setup:static-content:deploy -f en_US

echo "📇 Reindexing..."
bin/magento indexer:reindex

echo "🗑️  Flushing cache..."
bin/magento cache:flush

echo "🛠️  Setting developer mode..."
bin/magento deploy:mode:set developer

echo ""
echo "✅ Installation complete!"
echo ""
echo "🎉 Access your store:"
echo "   Frontend: https://app.acp-magento2.test/"
echo "   Admin: https://app.acp-magento2.test/admin"
echo "   Username: admin"
echo "   Password: Admin123!"
echo ""
echo "📚 ACP API Endpoints:"
echo "   POST   /rest/V1/acp/checkout_sessions"
echo "   GET    /rest/V1/acp/checkout_sessions/:id"
echo "   POST   /rest/V1/acp/checkout_sessions/:id"
echo "   POST   /rest/V1/acp/checkout_sessions/:id/complete"
echo "   POST   /rest/V1/acp/checkout_sessions/:id/cancel"
echo "   GET    /acp/feed (Product Feed)"
echo ""
echo "⚙️  Configure ACP: Stores > Configuration > Agentic Commerce Protocol"
