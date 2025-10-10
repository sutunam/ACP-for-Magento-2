# Testing the ACP Magento 2 Extension

## Quick Start

### 1. Set Up Local Environment

Follow the installation instructions in the README.md to set up your Magento 2 instance with the ACP module installed.

### 2. Test API Endpoints Manually

Access your Magento instance (adjust the URL based on your setup)

#### Create a Checkout Session

```bash
curl -X POST http://localhost/rest/V1/acp/checkout_sessions \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "items": [
        {
          "sku": "TEST-001",
          "quantity": 2,
          "price": 50.00
        }
      ]
    }
  }'
```

Response:
```json
{
  "checkout_session_id": "cs_abc123...",
  "status": "open",
  "items": [...],
  "total": 100.00,
  "currency": "USD",
  "created_at": "2025-01-10T12:00:00Z",
  "updated_at": "2025-01-10T12:00:00Z"
}
```

#### Get Checkout Session

```bash
curl http://localhost/rest/V1/acp/checkout_sessions/cs_abc123...
```

#### Update Checkout Session

```bash
curl -X POST http://localhost/rest/V1/acp/checkout_sessions/cs_abc123... \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "items": [
        {
          "sku": "TEST-001",
          "quantity": 3,
          "price": 50.00
        }
      ]
    }
  }'
```

#### Complete Checkout Session

```bash
curl -X POST http://localhost/rest/V1/acp/checkout_sessions/cs_abc123.../complete \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "payment_data": {
        "token": "tok_test_123"
      }
    }
  }'
```

#### Cancel Checkout Session

```bash
curl -X POST http://localhost/rest/V1/acp/checkout_sessions/cs_abc123.../cancel
```

### 3. Run Unit Tests

```bash
cd vendor/run-as-root/module-agentic-commerce-protocol
../../../vendor/bin/phpunit -c src/Test/Unit/phpunit.xml.dist
```

### 4. Run Integration Tests

```bash
bin/magento dev:tests:run integration --filter RunAsRoot\\AgenticCommerceProtocol
```

## Testing with Postman

Import this collection to test all endpoints:

1. Create a new Postman Collection
2. Add requests for each endpoint
3. Use `{{base_url}}` = your Magento base URL (e.g., `http://localhost`)
4. Set Content-Type header to `application/json`

## Debugging

### Check Module Status

```bash
bin/magento module:status RunAsRoot_AgenticCommerceProtocol
```

### View Logs

```bash
tail -f var/log/system.log
tail -f var/log/exception.log
```

### Clear Cache

```bash
bin/magento cache:flush
```

### Recompile

```bash
bin/magento setup:di:compile
```

## Common Issues

### Module Not Showing Up
```bash
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
```

### API Returns 404
- Make sure you've run `bin/magento setup:upgrade`
- Check that webapi.xml is properly configured
- Clear cache with `bin/magento cache:flush`

### Composer Issues
```bash
composer config repositories.local path "../src"
composer require run-as-root/module-agentic-commerce-protocol:@dev
```

## CI/CD Testing

GitHub Actions will automatically run tests on every push and PR.

Check the Actions tab on GitHub to see test results.
