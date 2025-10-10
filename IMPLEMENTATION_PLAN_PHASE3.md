# Implementation Plan: Phase 3 - Shipping Method Selection

## Stage 1: Accept fulfillment_option_id in Update Request
**Goal**: Parse and validate fulfillment_option_id from update request
**Success Criteria**:
- Extract fulfillment_option_id from request data
- Validate format matches shipping method codes (e.g., "flatrate_flatrate")
**Tests**: Send update with valid/invalid fulfillment_option_id
**Status**: Not Started

## Stage 2: Set Shipping Method on Quote
**Goal**: Apply selected shipping method to Quote
**Success Criteria**:
- Map fulfillment_option_id to Magento shipping method code
- Set on Quote's shipping address
- Validate method is available for the address
- Recalculate totals after setting
**Tests**: Verify shipping cost updates correctly
**Status**: Not Started

## Stage 3: Persist Selection in Session
**Goal**: Store selected shipping method in session
**Success Criteria**:
- Save fulfillment_option_id in session data
- Include in response
- Maintain selection through session lifecycle
**Tests**: Get session and verify selected method persists
**Status**: Not Started
