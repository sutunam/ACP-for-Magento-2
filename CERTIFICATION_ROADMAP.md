# ACP Certification Roadmap

This document outlines the remaining work needed to achieve 100% OpenAI ACP specification compliance for merchant certification.

## Current Status: 60% Spec-Compliant ✅

**What's Working:**
- ✅ All 5 REST endpoints (create, update, get, complete, cancel)
- ✅ Bearer token authentication
- ✅ Database persistence
- ✅ Magento Quote integration (real pricing, taxes, inventory)
- ✅ Stripe PaymentIntent integration
- ✅ Webhook notifications (order.created, order.updated)
- ✅ Product feed generator
- ✅ Multi-currency support
- ✅ Session cleanup cron
- ✅ Custom logging

**What's Tested:**
- ✅ Warden local environment
- ✅ End-to-end API flow
- ✅ Authentication working
- ✅ Product lookup and pricing
- ✅ Database confirmed

## CRITICAL Gaps (Required for Certification)

### 1. Response Schema Enhancement (Priority: HIGH)

**Current Response:**
```json
{
  "checkout_session_id": "cs_abc123",
  "status": "open",
  "items": "[{\"sku\":\"24-MB01\",\"quantity\":2}]",
  "total": 68,
  "currency": "USD"
}
```

**Required Response per Spec:**
```json
{
  "checkout_session_id": "cs_abc123",
  "status": "open",
  "payment_provider": "stripe",
  "line_items": [
    {
      "item_id": "item_1",
      "product_title": "Joust Duffle Bag",
      "sku": "24-MB01",
      "quantity": 2,
      "base_amount": {"amount": 68.00, "currency": "USD"},
      "discount_amount": {"amount": 0, "currency": "USD"},
      "tax_amount": {"amount": 5.44, "currency": "USD"},
      "total_amount": {"amount": 73.44, "currency": "USD"}
    }
  ],
  "total_details": {
    "subtotal_amount": {"amount": 68.00, "currency": "USD"},
    "shipping_amount": {"amount": 5.00, "currency": "USD"},
    "tax_amount": {"amount": 5.44, "currency": "USD"},
    "discount_amount": {"amount": 0, "currency": "USD"},
    "total_amount": {"amount": 78.44, "currency": "USD"}
  },
  "fulfillment_options": [
    {
      "id": "flatrate_flatrate",
      "type": "shipping",
      "amount": {"amount": 5.00, "currency": "USD"},
      "description": "Flat Rate - Fixed",
      "estimated_delivery_date": "2025-10-15"
    }
  ],
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
  }
}
```

**Implementation Tasks:**
- [ ] Create Response Builder service (`Model/Response/CheckoutSessionResponseBuilder.php`)
- [ ] Build line_items from Quote items with detailed pricing
- [ ] Build total_details from Quote totals
- [ ] Build fulfillment_options from shipping methods
- [ ] Add payment_provider field (hardcode "stripe" for now)
- [ ] Update CheckoutSessionInterface with new fields
- [ ] Create proper DTO classes for nested objects
- [ ] Update all endpoint responses to use Response Builder

**Estimated Time:** 6-8 hours

**Files to Create/Modify:**
- `Model/Response/CheckoutSessionResponseBuilder.php` (new)
- `Model/Response/LineItemBuilder.php` (new)
- `Model/Response/TotalDetailsBuilder.php` (new)
- `Model/Response/FulfillmentOptionsBuilder.php` (new)
- `Api/Data/CheckoutSessionInterface.php` (modify - add getters/setters)
- `Model/CheckoutSessionManagement.php` (modify - use response builder)

### 2. Required Headers Validation (Priority: HIGH)

**Currently Validating:**
- ✅ `Authorization: Bearer <token>`

**Missing Validation:**
- ❌ `Idempotency-Key` - Prevent duplicate requests
- ❌ `Request-Id` - Request tracking/correlation
- ❌ `Signature` - HMAC signature validation
- ❌ `Timestamp` - Replay attack prevention (reject requests >5 min old)
- ❌ `API-Version` - Version compatibility check

**Implementation Tasks:**
- [ ] Create `Model/Auth/HeaderValidator.php`
- [ ] Implement idempotency key storage/checking (Redis cache)
- [ ] Add signature validation using HMAC SHA256
- [ ] Add timestamp validation with configurable tolerance
- [ ] Store request_id for tracking
- [ ] Update `Plugin/ApiAuthenticationPlugin.php` to validate all headers
- [ ] Add config for signature secret (different from API key)

**Estimated Time:** 4-5 hours

**Files to Create/Modify:**
- `Model/Auth/HeaderValidator.php` (new)
- `Model/Auth/IdempotencyManager.php` (new)
- `Plugin/ApiAuthenticationPlugin.php` (modify)

### 3. Product Feed Spec Compliance (Priority: MEDIUM)

**Current Feed Fields:**
- sku, name, description, price, url, image_url, availability, categories, product_type

**Missing Required Fields:**
- `id` - Unique product ID (can use entity_id or sku)
- `title` - Map from name ✅ (just rename)
- `link` - Map from url ✅ (just rename)
- `brand` - Get from manufacturer attribute
- `gtin` / `mpn` - Universal product codes
- `enable_search` - Flag (default true)
- `enable_checkout` - Flag (default true)
- `images` - Array of image URLs (we only send 1)
- `variants` - For configurables (size, color options with SKUs)
- `inventory_quantity` - Stock level

**Implementation Tasks:**
- [ ] Add manufacturer attribute to collection
- [ ] Get all product images (not just primary)
- [ ] For configurables: load child products and build variants array
- [ ] Get stock quantity from StockRegistry
- [ ] Add enable_search/enable_checkout flags (configurable per product)
- [ ] Rename fields to match spec (name→title, url→link)
- [ ] Support multiple feed formats (JSON, CSV, TSV, XML)

**Estimated Time:** 4-6 hours

**Files to Modify:**
- `Model/Feed/ProductFeedGenerator.php` (major refactor)

### 4. Shipping Method Selection (Priority: MEDIUM)

**Current:** We accept fulfillment_address but not fulfillment_option_id

**Required:** Handle fulfillment_option_id in update endpoint

**Implementation Tasks:**
- [ ] When `fulfillment_option_id` provided in update request
- [ ] Map to Magento shipping method code
- [ ] Set shipping method on Quote: `$quote->getShippingAddress()->setShippingMethod($methodCode)`
- [ ] Recalculate totals to include selected shipping cost
- [ ] Validate shipping method is available for address

**Estimated Time:** 2 hours

**Files to Modify:**
- `Model/CheckoutSessionManagement.php` (update method)
- `Model/Address/AddressManagement.php` (add shipping method setter)

### 5. Order Reference in Complete Response (Priority: MEDIUM)

**Current:** Complete returns session with status="completed"

**Required:** Return order details for tracking

**Response Should Include:**
```json
{
  "checkout_session_id": "cs_abc123",
  "status": "completed",
  "order_id": "000000123",
  "order_number": "000000123",
  "order_url": "https://store.com/sales/order/view/order_id/123",
  "confirmation_email_sent": true,
  ...
}
```

**Implementation Tasks:**
- [ ] After order creation, add order data to session
- [ ] Generate order view URL
- [ ] Track if confirmation email was sent
- [ ] Return in complete response

**Estimated Time:** 1-2 hours

**Files to Modify:**
- `Model/CheckoutSessionManagement.php` (complete method)
- `Model/Order/OrderManagement.php` (return more order data)

## MEDIUM Priority Improvements

### 6. Proper Item ID Management

**Issue:** Spec uses "id" for items, we use internal structure

**Tasks:**
- [ ] Generate stable item_id for each line item
- [ ] Store item_id mapping in session
- [ ] Use in line_items response

**Estimated Time:** 1 hour

### 7. Discount Support

**Current:** Basic support via Quote

**Enhancement:**
- [ ] Apply coupon codes if provided in request
- [ ] Return applied discounts in line_items
- [ ] Show promotion details

**Estimated Time:** 2-3 hours

### 8. Gift Options

**If applicable:**
- [ ] Gift message support
- [ ] Gift wrap options
- [ ] Gift receipt

**Estimated Time:** 2-3 hours (optional)

## LOW Priority (Nice to Have)

### 9. MCP Server Support

The spec mentions MCP (Model Context Protocol) as an alternative to REST.

**Tasks:**
- [ ] Research MCP implementation
- [ ] Add MCP endpoint alongside REST
- [ ] Test with MCP clients

**Estimated Time:** 4-6 hours

### 10. Enhanced Error Responses

**Current:** Basic error messages

**Improvement:**
- [ ] Return errors array with multiple validation errors
- [ ] Add error_code, error_message, error_details
- [ ] Include field-level validation errors

**Estimated Time:** 2 hours

### 11. Performance Optimizations

**Tasks:**
- [ ] Cache shipping methods lookup
- [ ] Optimize Quote operations
- [ ] Add request/response compression
- [ ] Database query optimization

**Estimated Time:** 3-4 hours

## Implementation Plan

### Phase 1: Core Spec Compliance (Est: 2 days)
1. Response schema enhancement (8 hours)
2. Required headers validation (5 hours)
3. Order reference in complete (2 hours)

### Phase 2: Feed & Shipping (Est: 1 day)
4. Product feed spec compliance (6 hours)
5. Shipping method selection (2 hours)

### Phase 3: Polish (Est: 0.5 days)
6. Item ID management (1 hour)
7. Enhanced error responses (2 hours)
8. Testing & fixes (1 hour)

**Total Estimated Time:** 3-4 days of focused development

## Testing Checklist for Certification

Before submitting to OpenAI:

- [ ] All 5 endpoints return complete response schemas
- [ ] All required headers validated
- [ ] Product feed has all required fields
- [ ] Idempotency prevents duplicate orders
- [ ] Signature validation working
- [ ] Webhooks deliver successfully
- [ ] Payment processing works with real Stripe keys
- [ ] Can complete full purchase flow
- [ ] Error messages match spec error codes
- [ ] Performance meets requirements (< 2s response time)
- [ ] Works with ChatGPT test environment
- [ ] Passes OpenAI conformance tests

## Current Architecture Strengths

✅ **Solid Foundation:**
- Proper Magento patterns (Service Contracts, DI, ResourceModel)
- Real Quote integration (not just mock calculations)
- Actual Stripe SDK integration
- Webhook delivery with retry logic
- Proper error handling with custom exceptions
- Database schema with proper indexes
- Caching for performance
- Comprehensive configuration

✅ **Easy to Extend:**
- Modular code structure
- Clear separation of concerns
- Well-documented
- Test coverage foundation

## Reference Links

- **ACP Checkout Spec**: https://developers.openai.com/commerce/specs/checkout/
- **ACP Feed Spec**: https://developers.openai.com/commerce/specs/feed/
- **Delegated Payment Spec**: https://developers.openai.com/commerce/specs/payment/
- **OpenAPI Spec (GitHub)**: https://github.com/agentic-commerce-protocol/agentic-commerce-protocol/tree/main/spec/openapi
- **Stripe ACP Docs**: https://docs.stripe.com/agentic-commerce

## Next Steps

1. **Review this roadmap** and prioritize tasks
2. **Start with Phase 1** (Response Schema) - highest impact
3. **Test incrementally** after each phase
4. **Use Warden environment** for testing (already set up!)
5. **Submit for OpenAI review** after Phase 3 complete

## Notes for Developers

- All Quote data is already available - just need to map it to response format
- Magento handles most complexity (pricing, inventory, shipping)
- Focus on proper data transformation Quote → ACP format
- The response builders should be reusable services
- Keep backward compatibility during refactor
- Update unit tests as you add features

---

**Current Version:** V1.0 Beta (MVP/POC)  
**Target Version:** V1.5 (OpenAI Certified)  
**Repository:** https://github.com/run-as-root/ACP-for-Magento-2

**Maintainer:** Run_As_Root <hello@run-as-root.sh>
