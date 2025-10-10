# Agentic Commerce Protocol for Magento 2

**95% OpenAI ACP Spec Compliant | Pre-Production | Awaiting Platform Access**

Enable ChatGPT purchases directly from your Magento 2 store using the **Agentic Commerce Protocol** (ACP) - an open standard by OpenAI and Stripe.

> ⚠️ **Status:** This module is spec-compliant and fully tested with unit/integration tests, but has NOT been tested with actual OpenAI ChatGPT platform access. We are awaiting approval from OpenAI's merchant program. Use at your own risk until platform testing is complete.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Magento 2.4.6+](https://img.shields.io/badge/Magento-2.4.6%2B-orange.svg)](https://magento.com/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net/)

---

## 🚀 Features

### ✅ OpenAI ACP Specification Compliance (95%)

**Checkout Session API:**
- Enhanced response schema with `line_items`, `total_details`, `fulfillment_options`
- All monetary values as integers (cents) per spec
- Correct status enums: `not_ready_for_payment`, `ready_for_payment`, `completed`, `cancelled`
- Order tracking with `order_url` and `confirmation_email_sent`
- Shipping method selection via `fulfillment_option_id`

**Product Feed:**
- Spec-compliant fields: `id`, `title`, `link`, `brand`, `images`, `inventory_quantity`
- Configurable product variants with individual pricing
- Multiple image support (full gallery)
- Real-time inventory levels
- `enable_search` and `enable_checkout` flags

**Security Headers:**
- `Idempotency-Key` validation (Redis-backed, 24hr cache)
- `Request-Id` tracking for correlation
- `Timestamp` validation (5min tolerance, prevents replay attacks)
- `Signature` HMAC SHA256 validation (optional, configurable)
- `API-Version` compatibility checking

### Core Functionality
- ✅ Full REST API (5 endpoints: create, get, update, complete, cancel)
- ✅ Real Magento Quote integration (pricing, taxes, discounts, inventory)
- ✅ Stripe Delegated Payment with official SDK
- ✅ Webhook notifications (`order.created`, `order.updated`)
- ✅ Multi-currency support
- ✅ Database persistence with proper ResourceModel pattern
- ✅ Automated session cleanup cron job

### Security & Enterprise Features
- ✅ Bearer token API authentication
- ✅ Idempotency key duplicate prevention
- ✅ Timestamp-based replay attack prevention
- ✅ HMAC signature validation (configurable)
- ✅ Encrypted secret storage
- ✅ ACL permissions
- ✅ Comprehensive audit logging

### Developer Experience
- ✅ **39 Tests** (31 unit + 8 integration) covering critical paths
- ✅ Monetary conversion accuracy tests
- ✅ Security validation tests
- ✅ End-to-end API flow tests
- ✅ PSR-12 compliant code
- ✅ Proper Magento service contracts
- ✅ Full dependency injection
- ✅ Type-safe (strict_types everywhere)

---

## 📋 Requirements

- **Magento:** 2.4.6+
- **PHP:** 8.1+
- **Composer:** 2.x
- **Redis:** For caching (idempotency keys)
- **Stripe Account:** For payment processing

---

## 📦 Installation

### Via Composer (Recommended)

```bash
composer require run-as-root/module-agentic-commerce-protocol
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Manual Installation

```bash
git clone https://github.com/run-as-root/ACP-for-Magento-2.git
mkdir -p app/code/RunAsRoot/AgenticCommerceProtocol
cp -r ACP-for-Magento-2/* app/code/RunAsRoot/AgenticCommerceProtocol/
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuration

Navigate to **Stores > Configuration > Agentic Commerce Protocol**

### 1. General Settings
- **Enable Module:** Turn on/off the ACP functionality
- **API Key:** Generate secure key for OpenAI authentication
- **Test Mode:** Enable verbose logging for development
- **Session Retention:** Days to keep completed sessions (default: 30)

### 2. Security Settings (NEW)
- **Enable Signature Validation:** HMAC SHA256 request signing
- **Signature Secret:** Shared secret for signature verification
- **Timestamp Tolerance:** Replay attack window (default: 300 seconds)

### 3. Product Feed
- **Enable Product Feed:** Allow ChatGPT product discovery
- **Include Categories:** Filter by category (empty = all)
- **Maximum Products:** Limit feed size (default: 1000)

### 4. Webhook Settings
- **Enable Webhooks:** Send order events to OpenAI
- **Endpoint URL:** OpenAI webhook receiver
- **Signing Secret:** HMAC signature for webhooks

### 5. Stripe Payment
- **Enable Stripe:** Use Stripe for payment processing
- **Secret Key:** Your Stripe API key (sk_live_* or sk_test_*)
- **Test Mode:** Use Stripe test environment

---

## 🔌 API Endpoints

### Checkout Session API (Requires Authentication)

All endpoints require these headers:
```
Authorization: Bearer <api-key>
Idempotency-Key: <unique-request-id>
Request-Id: <correlation-id>
Timestamp: <unix-timestamp>
```

#### Create Session
```bash
POST /rest/V1/acp/checkout_sessions
Content-Type: application/json

{
  "items": [
    {"sku": "24-MB01", "quantity": 2}
  ]
}
```

#### Update Session
```bash
POST /rest/V1/acp/checkout_sessions/{id}

{
  "buyer": {
    "email": "customer@example.com",
    "first_name": "John",
    "last_name": "Doe"
  },
  "fulfillment_address": {
    "first_name": "John",
    "last_name": "Doe",
    "address_line1": "123 Main St",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "US"
  },
  "fulfillment_option_id": "flatrate_flatrate"
}
```

#### Get Session
```bash
GET /rest/V1/acp/checkout_sessions/{id}
```

#### Complete Session
```bash
POST /rest/V1/acp/checkout_sessions/{id}/complete

{
  "payment_data": {
    "token": "pm_stripe_token_here"
  }
}
```

#### Cancel Session
```bash
POST /rest/V1/acp/checkout_sessions/{id}/cancel
```

### Product Feed (Public)
```bash
GET /acp/feed
```

Returns JSON feed with all visible products in ACP format.

---

## 🧪 Testing

### Run Unit Tests (31 tests)
```bash
cd Test/Unit
../../../vendor/bin/phpunit
```

### Run Integration Tests (8 tests)
```bash
cd magento
bin/magento dev:tests:run integration RunAsRoot_AgenticCommerceProtocol
```

**Test Coverage:**
- ✅ Monetary conversion accuracy
- ✅ Security header validation
- ✅ Response schema compliance
- ✅ End-to-end checkout flows
- ✅ Idempotency and replay protection

---

---

## 🏗️ Architecture

Follows Magento 2 best practices:

**Structure:**
```
Model/
├── Response/              # ACP response builders
│   ├── CheckoutSessionResponseBuilder.php
│   ├── LineItemBuilder.php
│   ├── TotalDetailsBuilder.php
│   └── FulfillmentOptionsBuilder.php
├── Auth/                  # Security validators
│   ├── HeaderValidator.php
│   ├── IdempotencyManager.php
│   ├── SignatureValidator.php
│   └── TimestampValidator.php
├── CheckoutSessionManagement.php
├── Order/OrderManagement.php
└── Feed/ProductFeedGenerator.php

Plugin/
├── ApiAuthenticationPlugin.php
└── CheckoutSessionResponsePlugin.php

Test/
├── Unit/                  # 31 unit tests
└── Integration/          # 8 integration tests
```

**Patterns Used:**
- Service Contracts (`Api/`)
- Dependency Injection (constructor)
- Plugin/Interceptor pattern
- Repository pattern
- Builder pattern (responses)
- Strategy pattern (validators)

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/awesome-feature`)
3. Write tests for new functionality
4. Ensure all tests pass
5. Commit with clear message (`git commit -m 'feat: add awesome feature'`)
6. Push and open Pull Request

**Quality Standards:**
- ✅ Unit tests required for new code
- ✅ Integration tests for API changes
- ✅ PSR-12 coding standards
- ✅ Type hints and strict_types
- ✅ PHPStan level 8 compliance

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file

---

## 🔗 Resources

- **[OpenAI ACP Docs](https://developers.openai.com/commerce/)**
- **[ACP Specification (GitHub)](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol)**
- **[Stripe ACP Guide](https://docs.stripe.com/agentic-commerce)**
- **[Run_As_Root Website](https://run-as-root.sh/)**

---

## 💬 Support

**Issues & Questions:** [GitHub Issues](https://github.com/run-as-root/ACP-for-Magento-2/issues)

**Maintainer:** run_as_root GmbH <info@run-as-root.sh>

**Version:** 1.5 (OpenAI Certification Ready)

---

## 🎯 OpenAI Certification Status

**Current Compliance:** 95% (Spec-Compliant, Awaiting Platform Testing)

**Completed:**
- ✅ All required response fields per ACP spec
- ✅ Correct monetary value format (cents)
- ✅ Proper status enums
- ✅ Complete header validation
- ✅ Product feed spec compliance
- ✅ Security features (idempotency, replay protection)
- ✅ 39 comprehensive tests (unit + integration)

**Status:**
- ✅ Code complete and spec-compliant
- ✅ Unit and integration tests passing
- ⏳ **Awaiting OpenAI platform access for live testing**
- ⏳ Merchant application submitted, pending approval

**NOT YET TESTED WITH:**
- ❌ Actual ChatGPT Instant Checkout interface
- ❌ Real OpenAI API calls
- ❌ OpenAI conformance test suite

**Timeline:**
1. ⏳ Awaiting OpenAI merchant program approval
2. Platform testing & conformance tests (est. 2-3 weeks)
3. Bug fixes from platform testing (est. 1-2 weeks)
4. Final certification submission
5. Production deployment

**Application:** https://chatgpt.com/merchants
