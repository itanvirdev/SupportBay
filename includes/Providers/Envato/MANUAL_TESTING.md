# Envato Provider – Manual Testing Guide

## Purpose

This document describes the manual verification steps for the Envato Provider.

Unlike SupportBay module flow tests, the Envato Provider communicates with external services that require valid OAuth credentials and a live Envato account.

---

# Prerequisites

Before testing, ensure the following have been configured:

- Envato Client ID
- Envato Client Secret
- Redirect URI
- Provider enabled
- Valid Envato account
- Valid purchase code

---

# Environment Checklist

Verify the following:

- Provider exists in `wp_sbay_providers`
- Provider slug is `envato`
- Provider is enabled
- Provider configuration is saved
- Redirect URI matches the registered Envato application
- HTTPS is available (recommended)

---

# Provider Registration

## Verify

- Envato provider appears in the Integration Registry.
- Provider can be resolved through the Integration Manager.
- Provider reports as enabled.
- Provider metadata is correct.

Expected Result:

- Provider successfully boots without errors.

---

# OAuth Login

## Step 1

Open:

```text
/supportbay/oauth/envato/login
```

Expected Result:

- Browser redirects to Envato.

---

## Step 2

Approve the application.

Expected Result:

- Envato redirects back to SupportBay.

---

## Step 3

Verify callback.

Expected Result:

- Authorization code received.
- No PHP errors.
- No uncaught exceptions.

---

# Access Token

Verify:

- Authorization code exchanged successfully.
- Access token returned.
- Refresh token stored (if provided).
- Expiration calculated correctly.

Expected Result:

- Valid EnvatoToken object returned.

---

# Customer Retrieval

Retrieve the authenticated customer.

Verify:

- Envato ID
- Username
- Email (if available)
- Avatar
- Country

Expected Result:

- Valid EnvatoCustomer object returned.

---

# Purchase Verification

Use a valid purchase code.

Verify:

- Purchase found.
- Product ID returned.
- Product name returned.
- Buyer username matches.
- License type returned.
- Purchase date returned.
- Support expiration returned.

Expected Result:

- Valid EnvatoPurchase object returned.

---

# Invalid Purchase Code

Use an invalid purchase code.

Expected Result:

- EnvatoException thrown.
- User-friendly error displayed.
- No fatal errors.

---

# Expired Support

Use a purchase with expired support.

Verify:

- Purchase verifies successfully.
- Support expiration returned.
- Active support status is false.

Expected Result:

- Purchase remains valid.
- Support status indicates expired.

---

# Invalid OAuth Code

Use an invalid authorization code.

Expected Result:

- Authentication fails gracefully.
- EnvatoException thrown.
- No PHP warnings or fatal errors.

---

# Invalid Client Credentials

Configure an invalid Client ID or Client Secret.

Expected Result:

- OAuth exchange fails.
- Appropriate error is reported.
- No uncaught exceptions.

---

# Network Failure

Simulate an unavailable Envato API.

Expected Result:

- Request fails gracefully.
- EnvatoException thrown.
- Application remains stable.

---

# Provider Configuration

Verify the following values load correctly through `ProviderConfiguration`:

- Client ID
- Client Secret
- Redirect URI

Expected Result:

- Configuration values match the provider settings.

---

# Security Checklist

Verify:

- OAuth secrets are never displayed.
- Access tokens are never rendered in the UI.
- Purchase codes are never shown in full.
- Exceptions do not expose sensitive information.
- HTTPS is used for OAuth callbacks.

---

# Regression Checklist

Run after any provider-related changes.

- Provider registers successfully.
- OAuth login works.
- OAuth callback succeeds.
- Access token exchange succeeds.
- Customer retrieval succeeds.
- Purchase verification succeeds.
- Invalid purchase handling works.
- Invalid OAuth handling works.
- Provider configuration loads correctly.
- No PHP warnings or notices.
- No uncaught exceptions.

---

# Future Tests

When additional features are implemented, extend this checklist to include:

- Customer account linking
- Automatic WordPress account creation
- Purchase Verification module integration
- Ticket verification
- Support expiry rules
- Related tickets
- Refresh token flow
- Automatic token renewal
- Multiple provider support

---

# Status

Current Status:

- Provider registration
- OAuth architecture
- Customer retrieval
- Purchase retrieval
- Configuration service

Future Phases:

- Purchase Verification module
- Customer linking
- Ticket verification
- Dashboard integration
- Provider synchronization
