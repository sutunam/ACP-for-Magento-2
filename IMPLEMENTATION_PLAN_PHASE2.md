# Implementation Plan: ACP Certification Phase 2 - Header Validation

## Stage 1: Header Validator Infrastructure
**Goal**: Create header validation service with proper DI
**Success Criteria**:
- HeaderValidator class created
- Can extract and validate required headers
- Proper exception handling for missing/invalid headers
**Tests**: Test with missing headers, invalid formats
**Status**: Complete ✅

## Stage 2: Idempotency Key Management
**Goal**: Implement idempotency key storage and checking to prevent duplicate requests
**Success Criteria**:
- IdempotencyManager class created
- Uses Redis cache for key storage
- Checks key uniqueness before processing
- Returns cached response if key already exists
- Keys expire after configurable time (default 24 hours)
**Tests**: Test duplicate request returns same response
**Status**: Complete ✅

## Stage 3: HMAC Signature Validation
**Goal**: Validate request signatures using HMAC SHA256
**Success Criteria**:
- Signature validation using shared secret
- Compares computed signature with provided signature
- Configurable signature secret in admin
- Proper error messages for signature mismatches
**Tests**: Test with valid and invalid signatures
**Status**: Complete ✅

## Stage 4: Timestamp Validation
**Goal**: Prevent replay attacks by validating request timestamps
**Success Criteria**:
- Extract timestamp from headers
- Validate timestamp is within tolerance (default 5 minutes)
- Configurable tolerance in admin
- Rejects old/future timestamps
**Tests**: Test with old, current, and future timestamps
**Status**: Complete ✅

## Stage 5: Integration with API Authentication Plugin
**Goal**: Wire all validators into existing authentication plugin
**Success Criteria**:
- All headers validated on every request
- Request-Id logged for tracking
- API-Version checked for compatibility
- Proper error responses for validation failures
**Tests**: Full API flow with all headers
**Status**: Complete ✅
