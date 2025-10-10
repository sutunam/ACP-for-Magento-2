# Implementation Plan: ACP Certification Phase 1

## Stage 1: Response Builder Infrastructure
**Goal**: Create the foundational response builder services for transforming Quote data to ACP format
**Success Criteria**:
- CheckoutSessionResponseBuilder class created with proper DI
- Compiles successfully
- Can instantiate builder without errors
**Tests**: Unit test for builder instantiation
**Status**: Complete ✅

## Stage 2: Line Items Response Structure
**Goal**: Build line_items array from Quote items with detailed pricing breakdown
**Success Criteria**:
- LineItemBuilder creates proper line item structure
- Each item includes: item_id, product_title, sku, quantity, base_amount, discount_amount, tax_amount, total_amount
- Amounts properly formatted with currency
**Tests**: Test with 2-item quote, verify all fields populated correctly
**Status**: Complete ✅

## Stage 3: Total Details Response Structure
**Goal**: Build total_details from Quote totals aggregation
**Success Criteria**:
- TotalDetailsBuilder creates proper totals structure
- Includes: subtotal_amount, shipping_amount, tax_amount, discount_amount, total_amount
- All amounts match Quote calculations
**Tests**: Compare totals with Quote totals object
**Status**: Complete ✅

## Stage 4: Fulfillment Options Response
**Goal**: Build fulfillment_options from available shipping methods
**Success Criteria**:
- FulfillmentOptionsBuilder queries available methods for quote
- Returns array with id, type, amount, description, estimated_delivery_date
- Maps Magento shipping codes to ACP format
**Tests**: Test with multiple shipping methods available
**Status**: Complete ✅

## Stage 5: Integration & API Updates
**Goal**: Wire response builders into all 5 API endpoints
**Success Criteria**:
- All endpoints (create, update, get, complete, cancel) return enhanced response
- CheckoutSessionInterface updated with new getters
- No breaking changes to existing functionality
- All background tests still pass
**Tests**: Full API flow test with enhanced responses
**Status**: Complete ✅
