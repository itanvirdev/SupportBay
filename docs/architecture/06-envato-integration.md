# SupportBay – Envato Integration Specification (v1)

## Document Information

| Property     | Value                    |
| ------------ | ------------------------ |
| Document     | 06-envato-integration.md |
| Product      | SupportBay               |
| Version      | v1                       |
| Status       | Approved                 |
| Last Updated | June 2026                |

---

# Purpose

The Envato Integration allows customers to:

- Authenticate using Envato OAuth
- Verify purchases
- Link Envato accounts
- Submit verified support tickets
- Display purchase information to support staff

Envato is the first supported provider in SupportBay.

The architecture must support future providers without modifying the ticketing system.

Examples:

- Envato
- Easy Digital Downloads
- WooCommerce
- Freemius
- Paddle
- Lemon Squeezy
- Gumroad

---

# Architecture Philosophy

SupportBay should never directly depend on Envato inside the ticketing system.

Instead:

Customer  
↓  
Provider Layer  
↓  
SupportBay Core

Envato becomes a provider implementation.

---

# Provider Interface

All future providers should implement a common contract.

Examples:

verifyPurchase()

getCustomer()

getProducts()

getSupportExpiry()

getLicenseType()

refreshToken()

---

# Provider Identifier

Each provider has a unique identifier.

Examples:

envato  
edd  
woocommerce  
freemius  
paddle

---

# Customer Authentication

## Login Options

Customers may authenticate using:

- Manual Registration
- Envato OAuth

---

# Envato OAuth Flow

Customer Clicks:

Continue with Envato

↓

Redirect to Envato OAuth

↓

Customer Authorizes

↓

SupportBay Receives Authorization Code

↓

Exchange Code for Access Token

↓

Retrieve Customer Information

↓

Create or Link WordPress User

↓

Redirect to Customer Dashboard

---

# Stored Envato Profile Data

SupportBay stores:

- Envato User ID
- Envato Username
- Avatar URL
- Access Token
- Refresh Token (if available)
- Token Expiry Timestamp
- Connection Timestamp

---

# WordPress Account Linking

## Existing Customer

If the email already exists:

SupportBay links the Envato account.

---

## New Customer

SupportBay creates a new WordPress account.

Role:

Customer

State:

Registered

---

# Purchase Verification

## Ticket Creation

Verified tickets require:

Purchase Code

Field:

Purchase Code \*

Required for verified support.

---

# Verification Flow

Customer Submits Purchase Code

↓

SupportBay Calls Envato API

↓

Validate Purchase

↓

Retrieve Purchase Information

↓

Create Verification Record

↓

Attach Verification To Ticket

↓

Mark Ticket As Verified

---

# Verification Snapshot

A snapshot is stored during verification.

Reason:

Purchase data may change later.

Historical tickets should remain accurate.

---

# Stored Verification Data

SupportBay stores:

- Provider
- Purchase Code
- Product ID
- Product Name
- Buyer Username
- License Type
- Purchase Date
- Supported Until
- Verification Timestamp

---

# One Ticket = One Verification

Rule:

One Ticket  
↓  
References One Purchase Verification

One Purchase Verification  
↓  
Can Be Referenced By Multiple Tickets

Examples:

Ticket A  
↓  
Fixton Theme

Ticket B  
↓  
Rovix Theme

---

# Ticket Verification States

verified

unverified

guest

---

# Customer States

guest

registered

verified

suspended

---

# Support Expiry Handling

SupportBay stores:

supported_until

Example:

2026-12-01

---

# Support Status

active

expired

unknown

---

# Ticket Sidebar Display

Verified Ticket:

✓ Verified Purchase

Product:
Fixton Theme

License:
Regular

Support Until:
2026-12-01

Purchase Code: \***\*\*\*\*\*\*\***ABCD

---

Guest Ticket:

Guest Customer

No purchase verification available.

---

# Purchase Code Security

Purchase codes must never be displayed in full.

UI Example:

\***\*\*\*\*\*\*\***ABCD

---

# Token Storage

OAuth tokens must never be exposed in UI.

Tokens should be encrypted before storage.

---

# Token Refresh Strategy

When supported:

Access Token Expired  
↓  
Refresh Token  
↓  
Store New Access Token  
↓  
Continue Request

---

# Envato Sync

Manual Sync:

Manager clicks:

Refresh Verification

↓

SupportBay requests latest data.

↓

Verification record updated.

---

# Error Handling

## Invalid Purchase Code

Display:

Purchase code could not be verified.

---

## API Unavailable

Display:

Verification service temporarily unavailable.

Please try again later.

---

## Expired Support

Display:

Support period has expired.

Ticket creation may continue based on administrator settings.

---

# Settings

SupportBay  
↓  
Providers  
↓  
Envato

---

## OAuth Credentials

Client ID

Client Secret

Redirect URI

---

## Verification Settings

Require Purchase Verification

Yes / No

Allow Expired Support Tickets

Yes / No

Allow Guest Tickets

Yes / No

---

# Permissions

Customer

Can:

- Connect Envato account
- Verify purchases
- View own purchase information

Cannot:

- View OAuth tokens

---

Agent

Can:

- View verification summary
- View support status

Cannot:

- View tokens
- View full purchase code

---

Manager

Can:

- Refresh verification
- View verification history

Cannot:

- View tokens

---

Administrator

Can:

- Configure Envato integration
- Manage provider settings

Cannot:

- View raw OAuth secrets by default

---

# Future Provider Architecture

Future integrations must use the same provider system.

Examples:

EnvatoProvider

EDDProvider

WooCommerceProvider

FreemiusProvider

PaddleProvider

Each provider should implement a common interface.

No ticketing logic should be provider-specific.

---

# Additional Feature (Highly Recommended)

Show related tickets.

Example:

```
Verified Purchase

Product: Fixton Theme

License Type: Regular

Support Until: 2026-12-01

Related Tickets:

#54E5DF43
#82AB4D91
#9FD124EE
```

---

# Approved Decisions

✓ Envato is the first provider.

✓ OAuth authentication supported.

✓ Purchase verification supported.

✓ One ticket = References One Purchase Verification.

✓ One Purchase Verification = Can Be Referenced By Multiple Tickets.

✓ Verification snapshot stored.

✓ Purchase codes masked in UI.

✓ OAuth tokens encrypted.

✓ Provider-based architecture.

✓ Future providers use the same contract.

✓ Ticket system remains provider agnostic.

---
