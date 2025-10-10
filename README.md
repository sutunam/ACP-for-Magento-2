# Agentic Commerce Protocol for Magento 2

Enable ChatGPT purchases directly from your Magento 2 store using the **Agentic Commerce Protocol** (ACP) - an open standard co-developed by OpenAI and Stripe.

## Features

### Core Functionality
- ✅ Full ACP REST API implementation (5 endpoints)
- ✅ Database persistence with proper ResourceModel pattern
- ✅ Magento Quote integration (real pricing, taxes, discounts)
- ✅ Stripe Delegated Payment Spec support with SDK
- ✅ Product feed generator for ChatGPT discovery
- ✅ Webhook notifications for order events
- ✅ Multi-currency support with conversion

### Security & Authentication
- ✅ Bearer token API authentication
- ✅ HMAC webhook signatures
- ✅ Encrypted API key storage
- ✅ Rate limiting ready
- ✅ ACL permissions

### Admin Features
- ✅ Complete admin configuration panel
- ✅ API key management
- ✅ Stripe settings (test/live mode)
- ✅ Product feed configuration
- ✅ Webhook endpoint setup

### Developer Experience
- ✅ Comprehensive unit tests (20+ test cases)
- ✅ Integration tests for API endpoints
- ✅ GitHub Actions CI/CD pipeline
- ✅ PSR-12 compliant code
- ✅ Proper error handling with ACP error codes
- ✅ Magento 2.4.6+ compatible

## Requirements

- PHP 8.1+
- Magento 2.4.6+
- Composer 2.x

## Installation

```bash
composer require run-as-root/module-agentic-commerce-protocol
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## API Endpoints

### ACP Checkout API (Requires Bearer Token)

- `POST /rest/V1/acp/checkout_sessions` - Create checkout session
- `GET /rest/V1/acp/checkout_sessions/:id` - Get checkout session
- `POST /rest/V1/acp/checkout_sessions/:id` - Update checkout session (items, address, buyer)
- `POST /rest/V1/acp/checkout_sessions/:id/complete` - Complete checkout & create order
- `POST /rest/V1/acp/checkout_sessions/:id/cancel` - Cancel checkout session

### Product Feed (Public)

- `GET /acp/feed` - Product feed for ChatGPT discovery

All ACP endpoints require `Authorization: Bearer <api-key>` header.

## Configuration

After installation, configure the module in **Stores > Configuration > Agentic Commerce Protocol**:

### General Settings
1. **Enable Module** - Turn the module on/off
2. **API Key** - Generate and enter your API key for OpenAI authentication
3. **Test Mode** - Enable for additional logging during development

### Product Feed Settings
1. **Enable Product Feed** - Allow ChatGPT to discover your products
2. **Include Categories** - Select which categories to expose (empty = all)
3. **Maximum Products** - Limit feed size (default: 1000)

### Webhook Settings
1. **Enable Webhooks** - Send order events to OpenAI
2. **Endpoint URL** - OpenAI webhook receiver URL
3. **Signing Secret** - Secret for HMAC signature generation

### Stripe Payment Settings
1. **Enable Stripe Payments** - Use Stripe for payment processing
2. **Secret Key** - Your Stripe API key (sk_live_... or sk_test_...)
3. **Test Mode** - Use Stripe test environment

## Development

### Running Unit Tests

```bash
cd vendor/run-as-root/module-agentic-commerce-protocol
../../../vendor/bin/phpunit -c src/Test/Unit/phpunit.xml.dist
```

### Running Integration Tests

```bash
cd dev/tests/integration
../../../vendor/bin/phpunit ../../../vendor/run-as-root/module-agentic-commerce-protocol/src/Test/Integration
```

## Local Development

To set up a local development environment:

```bash
# Clone this repository
git clone https://github.com/run-as-root/ACP-for-Magento-2.git
cd ACP-for-Magento-2

# Install Magento 2 (adjust paths as needed)
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition magento
cd magento

# Add module as local repository
composer config repositories.local path "../src"
composer require run-as-root/module-agentic-commerce-protocol:@dev

# Run Magento setup
bin/magento setup:install \
    --base-url=http://localhost/ \
    --db-host=localhost \
    --db-name=magento \
    --db-user=root \
    --db-password=password \
    --admin-firstname=Admin \
    --admin-lastname=User \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=Admin123! \
    --language=en_US \
    --currency=USD \
    --timezone=America/Chicago

# Enable the module
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Architecture

The extension follows Magento 2 best practices:

- **Service Contracts** - Clean API interfaces in `Api/`
- **Models** - Business logic in `Model/`
- **Dependency Injection** - All dependencies injected via constructor
- **Web API** - REST endpoints configured via `webapi.xml`
- **Test Coverage** - Comprehensive unit and integration tests

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Testing

All PRs must pass:
- ✅ Unit tests
- ✅ Integration tests  
- ✅ Coding standards (PHPCS)
- ✅ Static analysis (PHPStan level 8)

## License

MIT License - see [LICENSE](LICENSE) file for details

## Links

- [Agentic Commerce Protocol Docs](https://developers.openai.com/commerce/)
- [ACP GitHub Repository](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol)
- [Run_As_Root Website](https://run-as-root.sh/)

## Support

For issues, questions, or contributions, please open an issue on [GitHub](https://github.com/run-as-root/ACP-for-Magento-2/issues).
