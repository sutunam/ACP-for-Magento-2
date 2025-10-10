# Agentic Commerce Protocol - Complete Setup Guide

This guide will walk you through setting up your Magento 2 store to accept ChatGPT purchases.

## Prerequisites

Before starting, ensure you have:
- ✅ Magento 2.4.6+ installed and running
- ✅ Composer 2.x
- ✅ PHP 8.1+
- ✅ Admin access to your Magento store
- ✅ Ability to modify server configuration

## Part 1: Install the Extension

### Step 1: Install via Composer

```bash
composer require run-as-root/module-agentic-commerce-protocol
bin/magento module:enable RunAsRoot_AgenticCommerceProtocol
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

### Step 2: Verify Installation

```bash
bin/magento module:status RunAsRoot_AgenticCommerceProtocol
```

You should see: `RunAsRoot_AgenticCommerceProtocol : Module is enabled`

## Part 2: Apply to OpenAI Merchant Program

### Step 1: Access ChatGPT Merchant Portal

1. Go to [OpenAI Merchant Portal](https://developers.openai.com/commerce/)
2. Sign in with your OpenAI account
3. Click "Apply to Sell on ChatGPT"

### Step 2: Submit Application

Provide:
- **Business Information**: Legal name, address, tax ID
- **Product Catalog**: Description of what you sell
- **Store URL**: Your Magento store URL
- **Expected Volume**: Estimated monthly orders

### Step 3: Wait for Approval

- Processing time: 1-2 weeks
- You'll receive email notification
- Approved merchants get access to merchant dashboard

## Part 3: Set Up Stripe Delegated Payment

### Step 1: Stripe Account Setup

1. Create or log into your [Stripe account](https://dashboard.stripe.com/)
2. Navigate to **Developers > API Keys**
3. Copy your **Secret Key** (starts with `sk_live_` or `sk_test_`)

### Step 2: Enable Shared Payment Token

1. Contact Stripe support to enable "Shared Payment Token" feature
2. Or use Stripe's ACP documentation: https://docs.stripe.com/agentic-commerce
3. This is required for the Delegated Payment Spec

### Step 3: Test Mode (Optional)

For testing:
- Use test API keys (`sk_test_...`)
- Use Stripe test tokens (`tok_visa`, `pm_card_visa`, etc.)
- No real money will be charged

## Part 4: Configure the Extension

### Step 1: Access Configuration

1. Log into Magento Admin
2. Navigate to **Stores > Configuration**
3. Find **Agentic Commerce Protocol** in the left sidebar

### Step 2: General Settings

**Enable Module**: Yes  
**API Key**: Generate a secure random string (e.g., `openssl rand -hex 32`)  
**Test Mode**: Yes (for testing), No (for production)  
**Session Retention**: 30 days (how long to keep old sessions)

```bash
# Generate secure API key
openssl rand -hex 32
```

### Step 3: Product Feed Settings

**Enable Product Feed**: Yes  
**Include Categories**: Select categories to expose (leave empty for all)  
**Maximum Products**: 1000 (adjust based on catalog size)

### Step 4: Webhook Settings

**Enable Webhooks**: Yes  
**Endpoint URL**: Provided by OpenAI after merchant approval  
**Signing Secret**: Provided by OpenAI after merchant approval

### Step 5: Stripe Payment Settings

**Enable Stripe Payments**: Yes  
**Secret Key**: Your Stripe secret key from Part 3  
**Test Mode**: Match your Stripe key type (test/live)

### Step 6: Save Configuration

Click **Save Config** and flush cache:

```bash
bin/magento cache:flush
```

## Part 5: Register with OpenAI

### Step 1: Provide API Endpoint

In the OpenAI Merchant Dashboard, register your API endpoint:

```
https://your-magento-store.com/rest/V1/acp/checkout_sessions
```

### Step 2: Provide Product Feed URL

```
https://your-magento-store.com/acp/feed
```

### Step 3: Provide API Key

Use the API key you generated in Part 4, Step 2.

OpenAI will use this for Bearer token authentication:
```
Authorization: Bearer your_api_key_here
```

### Step 4: Webhook Configuration

OpenAI will provide:
- Webhook endpoint URL
- Signing secret

Add these to your Magento config (Part 4, Step 4).

## Part 6: Testing

### Test 1: Product Feed

```bash
curl https://your-store.com/acp/feed
```

Should return JSON with your products.

### Test 2: Create Checkout Session

```bash
curl -X POST https://your-store.com/rest/V1/acp/checkout_sessions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your_api_key" \
  -d '{
    "data": {
      "items": [
        {"sku": "PRODUCT-SKU", "quantity": 1}
      ]
    }
  }'
```

Should return session with `checkout_session_id`.

### Test 3: ChatGPT Integration

Once approved by OpenAI:
1. Open ChatGPT
2. Ask: "Show me products from [Your Store Name]"
3. ChatGPT should discover your products
4. Try: "Add [Product Name] to cart"
5. Complete test purchase

## Common Issues & Solutions

### Issue: "ACP module is disabled"
**Solution**: Enable module in admin config (Part 4, Step 2)

### Issue: "Invalid API key"
**Solution**: Ensure Bearer token matches the API key in config exactly

### Issue: "Product with SKU %1 does not exist"
**Solution**: 
- Check product exists and is enabled
- Run: `bin/magento indexer:reindex`
- Verify product is in stock

### Issue: Product feed shows price: 0
**Solution**: 
- For configurable products, ensure child products have prices
- Clear product feed cache: `bin/magento cache:clean acp_product_feed`

### Issue: Webhook not received by OpenAI
**Solution**:
- Check webhook endpoint URL is correct
- Verify signing secret matches
- Check `var/log/acp.log` for webhook errors

### Issue: Payment declined
**Solution**:
- Verify Stripe secret key is correct
- Check Stripe dashboard for detailed error
- Ensure test mode settings match (test key = test mode on)

## Monitoring & Maintenance

### View Logs

```bash
# ACP-specific logs
tail -f var/log/acp.log

# General Magento logs
tail -f var/log/system.log
tail -f var/log/exception.log
```

### Check Checkout Sessions

```bash
# Connect to database
mysql -h db_host -u db_user -p db_name

# View recent sessions
SELECT checkout_session_id, status, total, currency, created_at 
FROM acp_checkout_session 
ORDER BY created_at DESC 
LIMIT 10;
```

### Session Cleanup

The module automatically cleans up old sessions daily at 2 AM.

Retention period is configurable (default: 30 days).

Manual cleanup:
```bash
DELETE FROM acp_checkout_session 
WHERE status IN ('completed', 'canceled') 
AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Production Checklist

Before going live:

- [ ] Module enabled in production mode
- [ ] Using live Stripe API keys (sk_live_...)
- [ ] Test mode disabled
- [ ] Product feed returns correct products
- [ ] Webhook endpoint configured correctly
- [ ] Test purchases complete successfully
- [ ] Monitoring/logging configured
- [ ] Session cleanup cron verified
- [ ] SSL certificate valid
- [ ] OpenAI merchant approval received

## Security Best Practices

1. **API Key**: Use a strong, randomly generated key (32+ characters)
2. **Stripe Keys**: Never commit API keys to version control
3. **HTTPS Only**: Ensure all endpoints use HTTPS
4. **Rate Limiting**: Consider adding rate limiting at server level
5. **Monitoring**: Set up alerts for failed payments/webhooks
6. **Regular Updates**: Keep module and Magento core updated

## Support

- **Documentation**: [ACP Docs](https://developers.openai.com/commerce/)
- **Issues**: [GitHub Issues](https://github.com/run-as-root/ACP-for-Magento-2/issues)
- **Stripe Support**: https://support.stripe.com/
- **OpenAI Support**: Via merchant dashboard

## Next Steps

Once live:
1. Monitor first few orders closely
2. Check webhook delivery in OpenAI dashboard
3. Review payment processing in Stripe
4. Gather customer feedback
5. Optimize product descriptions for AI discovery

Congratulations! Your store is now enabled for ChatGPT purchases! 🎉
