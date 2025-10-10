#!/bin/bash
set -e

echo "🚀 Installing Magento 2.4.7 with ACP Extension..."

# Check if already installed
if [ -d "magento" ]; then
    echo "⚠️  magento/ directory already exists. Skipping creation."
    cd magento
else
    # Create Magento project
    echo "📥 Creating Magento 2.4.7 project..."
    composer create-project --repository-url=https://repo.magento.com/ \
        magento/project-community-edition:2.4.7 magento
    cd magento
fi

# Add local module repository
echo "🔗 Adding ACP module as local repository..."
composer config repositories.acp-module path "../src"

# Require the module
echo "📦 Requiring ACP module..."
composer require run-as-root/module-agentic-commerce-protocol:@dev

echo "✅ Magento and ACP module installed!"
echo "📝 Next: Run bin/magento setup:install..."
