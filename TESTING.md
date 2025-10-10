# Testing the ACP Magento 2 Extension

## Quick Start

### 1. Set Up Local Environment with Warden

```bash
# Make sure Warden is installed
# https://docs.warden.dev/installing.html

# Run the setup script
./setup-warden.sh
```

This will:
- Start Warden services
- Install Magento 2.4.7
- Install the ACP extension
- Configure everything automatically

### 2. Test API Endpoints Manually

Access your Magento instance at `https://app.acp-magento2.test/`

#### Create a Checkout Session

```bash
curl -X POST https://app.acp-magento2.test/rest/V1/acp/checkout_sessions \
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
curl https://app.acp-magento2.test/rest/V1/acp/checkout_sessions/cs_abc123...
```

#### Update Checkout Session

```bash
curl -X POST https://app.acp-magento2.test/rest/V1/acp/checkout_sessions/cs_abc123... \
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
curl -X POST https://app.acp-magento2.test/rest/V1/acp/checkout_sessions/cs_abc123.../complete \
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
curl -X POST https://app.acp-magento2.test/rest/V1/acp/checkout_sessions/cs_abc123.../cancel
```

### 3. Run Unit Tests

```bash
warden shell
cd magento
vendor/bin/phpunit -c vendor/run-as-root/module-agentic-commerce-protocol/src/Test/Unit/phpunit.xml.dist
```

### 4. Run Integration Tests

```bash
warden shell
cd magento
bin/magento dev:tests:run integration --filter RunAsRoot\\AgenticCommerceProtocol
```

## Testing with Postman

Import this collection to test all endpoints:

1. Create a new Postman Collection
2. Add requests for each endpoint
3. Use `{{base_url}}` = `https://app.acp-magento2.test`
4. Set Content-Type header to `application/json`

## Debugging

### Check Module Status

```bash
warden shell
cd magento
bin/magento module:status RunAsRoot_AgenticCommerceProtocol
```

### View Logs

```bash
warden shell
cd magento
tail -f var/log/system.log
tail -f var/log/exception.log
```

### Clear Cache

```bash
warden shell
cd magento
bin/magento cache:flush
```

### Recompile

```bash
warden shell
cd magento
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
