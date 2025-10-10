# Agentic Commerce Protocol for Magento 2

Enable ChatGPT purchases directly from your Magento 2 store using the **Agentic Commerce Protocol** (ACP) - an open standard co-developed by OpenAI and Stripe.

## Features

- ✅ Full ACP REST API implementation
- ✅ Create, update, get, complete, and cancel checkout sessions
- ✅ Automatic cart total calculation
- ✅ Currency support from Magento store config
- ✅ Comprehensive unit and integration tests
- ✅ GitHub Actions CI/CD pipeline
- ✅ PSR-12 compliant code
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

The module exposes the following REST API endpoints according to the ACP specification:

- `POST /rest/V1/acp/checkout_sessions` - Create checkout session
- `GET /rest/V1/acp/checkout_sessions/:id` - Get checkout session
- `POST /rest/V1/acp/checkout_sessions/:id` - Update checkout session
- `POST /rest/V1/acp/checkout_sessions/:id/complete` - Complete checkout
- `POST /rest/V1/acp/checkout_sessions/:id/cancel` - Cancel checkout

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

## Warden Local Development

To set up a local development environment with Warden:

```bash
# Clone this repository
git clone https://github.com/run-as-root/ACP-for-Magento-2.git
cd ACP-for-Magento-2

# Initialize Warden environment
warden env-init <project-name> magento2

# Start Warden
warden env up

# Install Magento 2
warden shell
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition magento
cd magento
composer config repositories.local path "../src"
composer require run-as-root/module-agentic-commerce-protocol:@dev

# Setup Magento
bin/magento setup:install \
    --base-url=https://<project-name>.test/ \
    --db-host=db \
    --db-name=magento \
    --db-user=magento \
    --db-password=magento \
    --admin-firstname=Admin \
    --admin-lastname=User \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=Admin123 \
    --language=en_US \
    --currency=USD \
    --timezone=America/Chicago

# Enable the module
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
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
