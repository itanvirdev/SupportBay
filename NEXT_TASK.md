# NEXT_TASK.md

## Current Task

Implement provider-driven purchase verification.

### Files

- EnvatoProvider.php
- PurchaseVerificationProvider.php
- PurchaseVerificationData.php
- VerificationService.php
- FakePurchaseProvider.php
- ProviderVerificationFlowTest.php

### Goal

VerificationService should verify purchases through any provider without importing provider-specific classes.

### Success Criteria

- ProviderVerificationFlowTest passes.
- No duplicate verification records.
- Verification module remains provider-independent.
