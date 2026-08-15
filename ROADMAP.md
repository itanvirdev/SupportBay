# SupportBay Roadmap

> **Project Roadmap**
>
> This document tracks the overall progress of SupportBay.
>
> It represents the long-term development plan, current milestone, completed work, future features, and architectural direction.
>
> Last Updated: August 2026

---

# Project Vision

SupportBay is a next-generation customer support platform built specifically for WordPress.

Unlike traditional ticket plugins, SupportBay is designed around a provider-based architecture that allows multiple marketplaces, payment platforms, AI services, and external providers to integrate without modifying the core system.

Long-term goals include:

- Modern Ticket System
- Customer Portal
- OAuth Authentication
- Marketplace Verification
- License Management
- Billing Integration
- AI Assistant
- Live Chat
- REST API
- Webhooks
- Knowledge Base
- Analytics
- SaaS-ready Architecture

SupportBay should remain modular, extensible, and provider-independent.

---

# Architecture Philosophy

SupportBay follows modern software architecture principles.

```
Clean Architecture

↓

Dependency Injection

↓

Repository Pattern

↓

Domain Entities

↓

Service Layer

↓

Event Driven

↓

Provider Integrations

↓

Flow Testing
```

Every feature should integrate into the existing architecture.

Never bypass the architecture for convenience.

---

# Current Progress

Overall Progress

```
██████████████████████████░░░░░░░░

≈ 70%
```

Backend Foundation

```
██████████████████████████████

100%
```

Business Modules

```
███████████████████████████░░

95%
```

External Integrations

```
██████████░░░░░░░░░░░░░░░░░░░

35%
```

Customer Features

```
██░░░░░░░░░░░░░░░░░░░░░░░░░░░

10%
```

Admin Features

```
█░░░░░░░░░░░░░░░░░░░░░░░░░░░░

5%
```

---

# Completed Foundation

## Core Framework

Status

```
✅ Complete
```

Modules

- Dependency Injection Container
- Service Provider System
- Database Installer
- Migration Registry
- Idempotent dbDelta-compatible Schemas
- Validated Database Version Updates
- Database Migration Flow Test
- Repository Base
- Entity Base
- Event Dispatcher
- Event Listeners
- Testing Framework
- Assertions
- Flow Tests

---

# Core Architecture

Status

```
✅ Complete
```

Completed

- Container
- Foundation
- Events
- Database
- Testing
- Integrations Foundation

---

# Completed Modules

## Tickets

Status

```
✅ Complete
```

Completed

- Ticket Schema
- Entity
- Repository
- Service
- Events
- Listeners
- Flow Test

---

## Messages

Status

```
✅ Complete
```

Completed

- Schema
- Entity
- Repository
- Service
- Events
- Flow Test

---

## Departments

Status

```
✅ Complete
```

Completed

- Schema
- Entity
- Repository
- Service
- Flow Test

---

## Activities

Status

```
✅ Complete
```

Completed

- Timeline
- Activity Repository
- Activity Service
- Logging
- Flow Test

---

## Attachments

Status

```
✅ Complete
```

Completed

- Upload
- Repository
- Entity
- Service
- Flow Test

Future

- Image Optimization
- S3 Storage
- Virus Scanning

---

## Customers

Status

```
✅ Complete
```

Completed

- Customer Entity
- Repository
- Service
- Flow Test

Future

- Customer Profile
- Avatar
- Preferences

---

## Authentication

Status

```
✅ Complete
```

Completed

- Magic Login
- Auth Tokens
- Token States
- Token Types
- Repository
- Service
- Flow Test

Future

- OAuth
- Passwordless Login
- Two Factor Authentication

---

## Providers Module

Status

```
✅ Complete
```

Completed

- Provider Schema
- Entity
- Repository
- Service
- Registry
- Discovery
- Manager
- Flow Test

Purpose

Stores provider configuration.

This module is different from runtime integrations.

---

## Verification Module

Status

```
🟢 Foundation Complete
```

Completed

- Purchase Verification Schema
- Verification Entity
- Verification Repository
- Verification Service
- Verification Events
- Verification Listeners
- Verification Flow Test

Current

Provider-driven verification.

Pending

- Ticket Integration
- OAuth Verification
- Customer Verification History

---

# Integration Layer

Status

```
🟢 Foundation Complete
```

Completed

```
IntegrationProvider

PurchaseVerificationProvider

IntegrationManager

IntegrationRegistry

IntegrationDiscovery

PurchaseVerificationData
```

Purpose

Normalize every provider.

Future providers should require minimal implementation.

---

# Envato Provider

Status

```
🟡 In Progress
```

Completed

- Provider Structure
- API Client
- OAuth Service
- Purchase Service
- Customer Service
- DTOs
- Routes
- README
- Manual Testing Guide
- Provider-driven Purchase Verification
- Provider Verification Flow Test
- Provider-independent OAuth Login
- Customer Linking
- Encrypted OAuth Token Storage
- OAuth Flow Test
- Refresh Token Consumption
- Generic Customer Connect / Reconnect Flow
- Customer Profile Connection Status
- Cross-customer Identity Collision Protection

Current

Customer connection experience complete; real Envato credential testing remains.

Next

- Additional provider adapters

---

# Completed Milestone

Current Focus

```
Customer Portal Purchases and Tickets
```

Workflow

```
PurchaseVerificationProvider

↓

EnvatoProvider

↓

PurchaseVerificationData

↓

VerificationService

↓

Verification Database

↓

Provider Verification Flow Test

↓

Ticket Integration

↓

Ticket Verification Flow Test

↓

OAuthProvider

↓

Envato OAuth Login

↓

Customer Linking

↓

OAuth Flow Test

↓

Customer Portal REST API

↓

Customer Portal API Flow Test

↓

React + TypeScript Build

↓

React Application Shell

↓

Authenticated Dashboard

↓

React Portal Flow Test

↓

Ticket List and Detail Screens

↓

Customer-visible Message Thread

↓

Verified Purchases Screen

↓

Customer Ticket Creation

↓

Customer Replies

↓

Customer Attachment Uploads

↓

Ticket Close and Reopen Actions

↓

Secure Attachment Downloads

↓

Customer Profile

↓

Portal Authentication Experience

↓

Email Notification Foundation

↓

REST API and Webhooks Foundation
```

Goal

Completely decouple provider-specific APIs from SupportBay business logic.

---

# Next Milestone

After Customer Management Workspace

```
Provider Management Workspace
```

---

# Development Phases

## Phase 1

Core Framework

Status

```
✅ Complete
```

---

## Phase 2

Business Modules

Status

```
✅ Complete
```

---

## Phase 3

Provider Architecture

Status

```
🟢 Almost Complete
```

Remaining

- Additional marketplace providers

---

## Phase 4

Customer Experience

Status

```
🚧 Not Started
```

Includes

- Customer Dashboard
- Purchases
- Licenses
- Downloads
- Ticket History
- Profile

---

## Phase 5

Administration

Status

```
🚧 Not Started
```

Includes

- Settings UI
- Provider UI
- Reports
- Dashboard
- Logs
- Diagnostics

---

## Phase 6

API

Status

```
🚧 Not Started
```

Includes

- REST API
- Authentication
- API Tokens
- Webhooks

---

## Phase 7

AI

Status

```
🚧 Planned
```

Includes

- AI Replies
- Ticket Classification
- Suggested Responses
- Knowledge Base Search
- Embeddings
- Semantic Search

---

# Current Repository Status

```
Foundation                  ✅

Container                   ✅

Database                    ✅

Events                      ✅

Testing                     ✅

Tickets                     ✅

Messages                    ✅

Departments                 ✅

Activities                  ✅

Attachments                 ✅

Customers                   ✅

Authentication              ✅

Providers                   ✅

Verification Foundation     ✅

Integration Foundation      ✅

Envato Provider             🚧

OAuth                       ⏳

Ticket Verification         ⏳

Customer Portal             ⏳

REST API                    ⏳

Admin UI                    ⏳

AI                          ⏳
```

---

# ============================================================================

# DETAILED FEATURE ROADMAP

# ============================================================================

This section tracks every planned feature.

Status Legend

```
✅ Completed

🚧 In Progress

⏳ Planned

❌ Not Started
```

---

# CUSTOMER PORTAL

Status

```
🚧 In Progress
```

Purpose

Provide a modern customer experience completely independent from wp-admin.

---

## Authentication

Status

```
🟢 Foundation Complete
```

Features

- Magic Login ✅
- OAuth Login ✅
- Provider Connect / Reconnect ✅
- Connected Provider Status ✅
- Password Login
- Remember Me
- Logout ✅
- Session Management

Future

- Two Factor Authentication

---

## Dashboard

Status

```
✅ Complete
```

Features

- Welcome Panel
- Active Tickets
- Recent Replies
- Purchases
- Licenses
- Downloads
- Notifications

---

## Purchases

Status

```
✅ Complete
```

Features

- Verified Purchases
- Product Information
- License Type
- Purchase Date
- Support Expiry

Future

- Renewal
- Billing
- Invoices

---

## Licenses

Status

```
❌ Not Started
```

Features

- License Keys
- Activation Limits
- Active Domains
- Deactivate License
- Regenerate License

Initially

- LemonSqueezy
- Future EDD
- Future Freemius

---

## Downloads

Status

```
❌ Not Started
```

Features

- Latest Versions
- Previous Versions
- Changelog
- Download Counter

---

## Tickets

Status

```
🚧 In Progress
```

Features

- Create Ticket ✅
- Reply ✅
- Close ✅
- Reopen ✅
- Attachments ✅
- Public Ticket
- Ticket Timeline

---

## Profile

Status

```
🚧 In Progress
```

Features

- Avatar
- Display Name ✅ Read-only
- Email ✅ Read-only
- Connected Providers
- Password
- Notification Preferences
- Company, phone, country, timezone, and language ✅

---

# ADMIN PANEL

Status

```
🚧 In Progress
```

Purpose

Manage the complete SupportBay installation.

---

## Dashboard

Status

```
✅ Foundation Complete
```

Features

- Open Tickets
- Waiting Replies
- Active Customers
- Verification Statistics
- Provider Health

---

## Ticket Management

Status

```
✅ Complete
```

Features

- Filters ✅
- Bulk Actions ✅
- Assign Agent ✅
- Merge Tickets ✅
- Split Ticket ✅
- Internal Notes ✅

---

## Customer Management

Status

```
✅ Complete
```

Features

- Customer Search ✅
- Linked Providers ✅
- Purchase History ✅
- Ticket History ✅
- Customer 360 Profile ✅
- Suspend / Reactivate ✅

---

## Provider Management

Status

```
✅ Complete
```

Features

- Enable / Disable ✅
- Configure Credentials ✅
- Health Status ✅
- Connection Test ✅

---

## Verification Management

Clarification

Verification records are backend entitlement state used during ticket creation and in ticket/customer context. A standalone verification detail workflow is not required.

Features

- Search Verification
- Refresh
- Reverify
- Revoke
- View Snapshot

---

## Settings

Features

General

Authentication

Providers

Notifications

Attachments

Emails

API

Security

AI

---

# REST API

Status

```
🟡 Foundation Complete
```

Completed

- Customer portal endpoints
- Administrator ticket list and detail
- Administrator ticket messages and replies
- Administrator ticket close and reopen
- Administrator customer list and detail
- Administrator department list and detail
- Secret-safe provider list and detail
- Administrator purchase verification list and detail
- WordPress capability enforcement
- Protected SupportBay customer, agent, and manager roles
- Capability-specific administrator mutation policies
- Customer state mutations
- Department creation and updates
- Provider enable and disable actions
- Provider-backed verification refresh and revoke actions
- Standard response envelope and pagination metadata

Pending endpoints

Authentication

Customers

Tickets

Messages

Attachments

Providers

Verifications

Licenses

Downloads

Future

GraphQL

---

# WEBHOOKS

Status

```
🟡 Foundation Complete
```

Incoming

OAuth

License Updates

Provider Events

Outgoing

Ticket Created ✅

Message Created ✅

Ticket Closed ✅

Ticket Reopened ✅

Verification Created

Verification Expired

Customer Registered

Pending

- Persistent destinations
- Signing secrets
- HTTP delivery providers
- Retries

---

# EMAIL SYSTEM

Status

```
🟡 Foundation Complete
```

Features

Immediate WordPress Email Delivery ✅

Provider-independent Channel Contract ✅

Ticket and Reply Event Listeners ✅

Deterministic Notification Flow Test ✅

Notification Delivery Logs ✅

Successful / Failed Attempt Auditing ✅

Email Template Foundation ✅

Template REST API ✅

Template Preview and Test Email ✅

React Notification Template Settings ✅

Notification Preferences and Event Enablement ✅

Ticket Lifecycle Notifications ✅

Ticket Assignment Notifications ✅

Ticket Reassignment Notifications ✅

Ticket Resolution Workflow and Notification ✅

React Notification Delivery Diagnostics ✅

Notification Log Retention and Cleanup ✅

Notification Delivery Metrics and Reporting ✅

Ticket Performance Reporting ✅

Report Export Foundation ✅

Ticket SLA Metrics and Response-Time Bands ✅

SLA Due-State and Ticket Queue Indicators ✅

Scheduled SLA Breach Detection and Domain Events ✅

SLA Breach Notifications for Assigned Staff ✅

Saved Replies Foundation ✅

React Saved Reply Composer Integration ✅

Saved Reply Management Settings UI ✅

Saved Reply Usage Tracking ✅

Saved Reply Sorting by Usage ✅

Saved Reply Categories ✅

Saved Reply Dynamic Placeholders ✅

Saved Reply Department Scoping ✅

Queue ✅

Asynchronous Initial Delivery ✅

Atomic Pending Dispatch ✅

Manual Retry Foundation ✅

Three-attempt Enforcement ✅

Scheduled Retry Worker ✅

Exponential Retry Scheduling ✅

Bounded Cron Retry Processing ✅

SMTP ⏳

Future

Amazon SES

Mailgun

Postmark

Resend

---

# KNOWLEDGE BASE

Status

```
❌ Planned
```

Features

Categories

Articles

Search

Suggestions

Related Articles

Future

AI Search

---

# LIVE CHAT

Status

```
❌ Planned
```

Features

Real-time Chat

Offline Messages

Transfer Agent

Visitor Tracking

Future

AI Assistant

---

# REPORTING

Status

```
❌ Planned
```

Reports

Ticket Volume

Response Time

Resolution Time

Agent Performance

Customer Satisfaction

Verification Statistics

Provider Statistics

---

# AI ROADMAP

Status

```
⏳ Planned
```

Provider Architecture

```
AIProvider
```

Supported Providers

```
OpenAI

Gemini

Claude
```

Planned Features

- Suggested Replies
- Ticket Classification
- Spam Detection
- Sentiment Analysis
- Knowledge Base Search
- Semantic Search
- AI Drafts
- AI Translation
- AI Summaries

Future

Conversation Memory

Embeddings

Vector Search

---

# PROVIDER ECOSYSTEM

Marketplace Providers

```
Envato
```

Status

```
🚧 In Progress
```

---

```
Easy Digital Downloads
```

Status

```
⏳ Planned
```

---

```
WooCommerce
```

Status

```
⏳ Planned
```

---

```
Freemius
```

Status

```
⏳ Planned
```

---

```
Paddle
```

Status

```
⏳ Planned
```

---

```
LemonSqueezy
```

Status

```
⏳ Planned
```

---

```
Gumroad
```

Status

```
⏳ Planned
```

---

# NOTIFICATION PROVIDERS

SMTP

Slack

Discord

Telegram

Microsoft Teams

---

# STORAGE PROVIDERS

Amazon S3

Cloudflare R2

DigitalOcean Spaces

Backblaze B2

---

# DEVELOPMENT ORDER

Current

```
✔ Foundation

✔ Modules

✔ Verification Foundation
```

↓

Current Sprint

```
Ticket Purchase Entitlement Enforcement
```

↓

Next Sprint

```
To be selected after entitlement enforcement
```

↓

Sprint 5

```
REST API

↓

Webhooks

↓

Notifications
```

↓

Sprint 6

```
Admin Dashboard

↓

Reporting

↓

Settings UI
```

↓

Sprint 7

```
Knowledge Base

↓

AI Assistant

↓

Live Chat
```

---

# VERSION ROADMAP

Version 1.0

Goal

```
Production Ready
```

Includes

- Ticket System
- Customer Portal
- Envato
- Verification
- OAuth
- REST API

---

Version 1.5

Goal

Marketplace Expansion

Includes

EDD

WooCommerce

Freemius

Downloads

Licenses

---

Version 2.0

Goal

AI Powered Support

Includes

OpenAI

Gemini

Claude

Knowledge Base

AI Suggestions

---

Version 3.0

Goal

Enterprise Platform

Includes

Live Chat

Teams

Advanced Reporting

Automation

Workflow Rules

SaaS Readiness

---

# LONG-TERM VISION

SupportBay should become a provider-independent customer support platform capable of serving:

- WordPress themes
- WordPress plugins
- SaaS products
- Digital downloads
- Software licensing
- Subscription businesses

The architecture should support adding new providers by implementing integration contracts rather than modifying existing business modules.

Core principles:

- Modular architecture
- Strong separation of concerns
- Provider independence
- Event-driven workflows
- Comprehensive flow testing
- Long-term maintainability

============================================================================

End of ROADMAP.md

Version: 1.0

Project:
SupportBay

Maintained by:
Tanvir Ahamed + OpenAI ChatGPT

============================================================================
