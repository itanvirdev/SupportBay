# MASTER_PLAN.md

# SupportBay Master Plan

Version: 1.0

Project:
SupportBay

Last Updated:
August 2026

Authors

Tanvir Ahamed
OpenAI ChatGPT

---

# Purpose

This document represents the long-term vision of SupportBay.

Unlike AGENTS.md, which explains HOW to build SupportBay, this document explains WHY SupportBay exists, WHAT problems it solves, and WHERE the project is heading over the next several years.

Every architectural decision should support this vision.

This document should be read before making any major architectural changes.

---

# Vision

SupportBay is not intended to become another WordPress ticket plugin.

SupportBay is designed to become a modern, modular customer support platform capable of serving digital businesses of every size.

Initially SupportBay focuses on WordPress products.

Eventually it should support:

• WordPress Themes
• WordPress Plugins
• SaaS Products
• Software Licenses
• Digital Downloads
• Subscription Products
• Agencies
• Marketplaces

SupportBay should be capable of supporting both a single product creator and a large software company.

---

# Mission

Create the most extensible and provider-independent support platform available for WordPress.

Everything should be modular.

Everything should be replaceable.

Nothing should depend on a specific provider.

---

# Philosophy

SupportBay follows one simple philosophy:

Core owns business.

Providers own integrations.

Never mix them.

Business logic should never know how Envato works.

Business logic should never know how Paddle works.

Business logic should never know how LemonSqueezy works.

Instead

SupportBay

↓

Integration Contracts

↓

Concrete Provider

↓

External Service

This principle is the foundation of the entire project.

---

# Long-Term Goal

Within the next several years SupportBay should become an ecosystem rather than a plugin.

Examples

SupportBay Core

↓

SupportBay Providers

↓

SupportBay Customer Portal

↓

SupportBay AI

↓

SupportBay Live Chat

↓

SupportBay Knowledge Base

↓

SupportBay Analytics

↓

SupportBay Mobile API

↓

SupportBay SaaS

The architecture should support this growth without requiring large rewrites.

---

# Architectural Principles

SupportBay follows several architectural principles.

---

1. Separation of Concerns

Every class has one responsibility.

Examples

Repository

↓

Database

Service

↓

Business Logic

Entity

↓

Domain State

Provider

↓

External API

Listener

↓

Side Effects

Event

↓

Business Change

---

2. Dependency Injection

Everything resolves through the Container.

Never manually create services inside business logic.

Correct

Container

↓

Repository

↓

Service

Wrong

Service

↓

new Repository()

---

3. Provider Independence

The ticket system should never know whether a purchase came from:

Envato

EDD

WooCommerce

Freemius

Paddle

LemonSqueezy

Instead

PurchaseVerificationProvider

↓

PurchaseVerificationData

↓

VerificationService

This architecture allows unlimited providers.

---

4. Event Driven

Business actions create Events.

Events create Listeners.

Listeners create side effects.

Business logic never directly performs unrelated actions.

Example

Ticket Created

↓

Event

↓

Listener

↓

Send Email

↓

Create Activity

↓

Notify Slack

Business logic remains simple.

---

5. Strong Typing

SupportBay should use modern PHP.

Requirements

strict_types

Enums

Typed Properties

Constructor Promotion

Readonly Dependencies

Named Arguments

Avoid weak typing.

---

6. Modularity

Every feature belongs to one module.

Every module owns:

Database

Entity

Repository

Service

Events

Listeners

Flow Tests

No module should depend on the internal implementation of another module.

---

# Design Goals

The architecture should be:

Simple

↓

Predictable

↓

Reusable

↓

Extensible

↓

Maintainable

↓

Testable

Future development should become easier, not harder.

---

# Core Principles

SupportBay should always prefer:

Composition

over

Inheritance

Interfaces

over

Concrete Implementations

Events

over

Hidden Coupling

Contracts

over

Provider Logic

Small Services

over

God Classes

---

# Business Principles

A support system is more than tickets.

SupportBay should eventually manage the complete customer lifecycle.

Customer

↓

Authentication

↓

Purchase

↓

Verification

↓

License

↓

Support

↓

Renewal

↓

Billing

↓

Retention

Every future feature should fit into this lifecycle.

---

# Customer Journey

The ideal customer experience is:

Discover Product

↓

Purchase Product

↓

Connect Account

↓

Verify Purchase

↓

Access Dashboard

↓

Download Product

↓

Manage License

↓

Create Ticket

↓

Receive Support

↓

Renew Support

↓

Continue Using Product

SupportBay should manage every stage.

---

# Provider Philosophy

Providers should behave like adapters.

They translate external systems into SupportBay language.

Example

Envato Response

↓

PurchaseVerificationData

↓

Verification Entity

↓

Database

The database never stores raw provider responses except for historical snapshots.

---

# Verification Philosophy

Verification is not an Envato feature.

Verification is a SupportBay feature.

Providers simply supply verification data.

SupportBay owns:

Verification

Status

History

Relationships

Business Rules

Providers simply answer:

"Is this purchase valid?"

---

# Ticket Philosophy

A ticket should represent a customer conversation.

Not a purchase.

Not an order.

Not a provider.

Tickets reference verification.

Verification references provider.

This keeps the ticket system completely provider-independent.

---

# Authentication Philosophy

Authentication should remain independent from providers.

SupportBay supports:

WordPress Login

Magic Login

OAuth

Future

Passwordless

Two Factor

Every authentication method should eventually create the same authenticated SupportBay customer.

---

---

# SYSTEM BLUEPRINT

---

SupportBay is built as a layered architecture.

Every layer has a single responsibility.

No layer should bypass another.

System Overview

```
Browser

↓

WordPress

↓

SupportBay Core

↓

Business Modules

↓

Integration Layer

↓

External Providers
```

The Core should never depend on Business Modules.

Business Modules should never depend on concrete Providers.

Providers should never contain SupportBay business logic.

---

# PROJECT STRUCTURE

The entire project is divided into five major areas.

```
SupportBay

├── assets/
│
├── docs/
│
├── includes/
│   ├── Common/
│   ├── Core/
│   ├── Modules/
│   ├── Providers/
│   └── Dev/
│
└── supportbay.php
```

Each directory has one responsibility.

---

# COMMON

Purpose

Reusable code shared across the project.

Examples

```
Enums

Helpers

Traits

Utilities

Contracts

Functions
```

Rules

Common must never depend on Modules.

Common should be reusable.

---

# CORE

Purpose

Acts as the application framework.

Core should contain zero business logic.

Examples

```
Container

Database

Events

Entities

Foundation

Integrations

Testing
```

Think of Core as SupportBay's own mini framework.

---

# MODULES

Purpose

Business logic.

Every feature belongs to exactly one module.

Current Modules

```
Tickets

Messages

Departments

Activities

Attachments

Customers

Authentication

Providers

Verifications
```

Future Modules

```
Notifications

Licenses

Downloads

Billing

Knowledge Base

Analytics

AI

Settings

Reports
```

---

# PROVIDERS

Purpose

Connect SupportBay with external services.

Current

```
Envato
```

Future

```
EDD

WooCommerce

Freemius

Paddle

LemonSqueezy

OpenAI

Gemini

Claude

Slack

Discord
```

Providers never own business rules.

---

# DEV

Purpose

Development tools.

Contains

```
Flow Tests

Fake Providers

Testing Utilities

Development Helpers
```

Never ship debugging tools to production.

---

# APPLICATION FLOW

Every request should follow the same path.

```
HTTP Request

↓

Controller

↓

Service

↓

Repository

↓

Database

↓

Entity

↓

Response
```

Never skip layers.

---

# BUSINESS FLOW

Business operations should always follow this order.

```
Input

↓

Validation

↓

Business Rules

↓

Repository

↓

Events

↓

Listeners

↓

Response
```

Business logic should remain predictable.

---

# MODULE BLUEPRINT

Every module should follow the same structure.

```
Module

├── Database

├── Entities

├── Enums

├── Events

├── Listeners

├── Repositories

├── Services

├── Testing

└── ModuleServiceProvider
```

Optional folders

```
Contracts

DTO

Policies

Factories

ValueObjects
```

---

# MODULE RESPONSIBILITIES

Database

↓

Schema

Entity

↓

State

Repository

↓

Persistence

Service

↓

Business Logic

Events

↓

Domain Changes

Listeners

↓

Side Effects

Testing

↓

Workflow Verification

---

# DATABASE PHILOSOPHY

Every business module owns its own table.

Examples

```
Tickets

Messages

Activities

Attachments

Customers

Departments

Providers

Purchase Verifications

Auth Tokens
```

No module should modify another module's schema directly.

---

# ENTITY PHILOSOPHY

Entities represent domain state.

Entities should be immutable whenever practical.

Entities should expose helper methods.

Examples

```
isOpen()

isClosed()

isExpired()

canRefresh()

isVerified()
```

Entities never

- execute SQL
- call APIs
- dispatch events

---

# REPOSITORY PHILOSOPHY

Repositories only communicate with the database.

Repositories should not

- validate
- dispatch events
- call providers
- perform workflows

Repositories convert

Database Row

↓

Entity

Nothing more.

---

# SERVICE PHILOSOPHY

Services contain business rules.

Services coordinate

Repository

↓

Provider

↓

Events

↓

Listeners

↓

Domain

Services should remain readable.

If a service becomes difficult to understand, split it.

---

# EVENT ARCHITECTURE

SupportBay is event-driven.

Business Action

↓

Event

↓

Listeners

↓

Side Effects

Example

```
Verification Verified

↓

VerificationVerified Event

↓

Create Activity

↓

Notify Customer

↓

Refresh Cache

↓

Update Analytics
```

Each listener should have one responsibility.

---

# EVENT PRINCIPLES

Events describe

Something happened.

Not

Something should happen.

Correct

```
TicketCreated

MessagePosted

VerificationRevoked
```

Incorrect

```
CreateTicket

SendEmail

UpdateDatabase
```

---

# TESTING STRATEGY

SupportBay uses workflow testing.

Unit Tests verify

One class.

Flow Tests verify

Complete business workflow.

Flow Tests should test

```
Create

↓

Retrieve

↓

Update

↓

Events

↓

Business Rules

↓

Delete
```

Avoid testing implementation details.

---

# INTEGRATION ARCHITECTURE

SupportBay communicates through contracts.

```
SupportBay

↓

Integration Contract

↓

Provider

↓

External API
```

Business modules should never import

```
Envato

EDD

WooCommerce
```

Instead they import

```
PurchaseVerificationProvider
```

This keeps the architecture provider-independent.

---

# PROVIDER LIFECYCLE

Every provider follows the same lifecycle.

```
Discover

↓

Register

↓

Boot

↓

Connect

↓

Use

↓

Disconnect
```

SupportBay should not know how each provider works internally.

---

# DATA NORMALIZATION

External providers return different data.

SupportBay normalizes everything.

Example

```
Envato Purchase

↓

PurchaseVerificationData

↓

Verification Entity
```

Tomorrow

```
EDD License

↓

PurchaseVerificationData

↓

Verification Entity
```

VerificationService never changes.

---

# DATABASE SNAPSHOTS

Historical information must be preserved.

Provider

↓

Verification Snapshot

↓

Database

↓

Ticket

Even if the provider changes later,

the historical ticket should remain accurate.

---

# MODULE COMMUNICATION

Modules communicate through

Services

Events

Contracts

Never directly through SQL.

Never directly through Provider APIs.

---

# DEPENDENCY RULE

Allowed

```
Modules

↓

Core
```

Allowed

```
Providers

↓

Core
```

Allowed

```
Modules

↓

Integration Contracts
```

Not Allowed

```
Core

↓

Modules
```

Not Allowed

```
Verification

↓

Envato
```

Not Allowed

```
Ticket

↓

Envato
```

---

# SYSTEM PRINCIPLE

SupportBay should always be expandable.

Adding a new provider should require

- new Provider
- new Service Provider
- configuration

NOT

rewriting Tickets,

Customers,

Authentication,

Verification,

or Core.

If adding a provider requires changing multiple existing modules, the architecture should be reconsidered.

---

---

# PRODUCT BLUEPRINT

---

SupportBay is more than a support ticket plugin.

It is designed as a complete Customer Success Platform.

The ticket system is only one part of the entire ecosystem.

The long-term product consists of multiple independent systems working together.

```
Customer

↓

Authentication

↓

Provider Connection

↓

Purchase Verification

↓

Customer Portal

↓

Downloads

↓

Licenses

↓

Support

↓

Billing

↓

Notifications

↓

AI

↓

Analytics
```

Every new feature should fit naturally into this lifecycle.

---

# PRODUCT ECOSYSTEM

The complete SupportBay ecosystem consists of multiple applications.

```
SupportBay Core

↓

Customer Portal

↓

Admin Panel

↓

Provider Integrations

↓

REST API

↓

AI Services

↓

Knowledge Base

↓

Notifications

↓

Analytics

↓

Future SaaS
```

Every application communicates through the Core.

The Core remains the single source of truth.

---

# CUSTOMER JOURNEY

SupportBay should manage the entire customer lifecycle.

```
Visitor

↓

Purchase Product

↓

Connect Provider

↓

Verify Purchase

↓

Create Customer

↓

Customer Dashboard

↓

Download Product

↓

Manage License

↓

Create Ticket

↓

Receive Support

↓

Renew Support

↓

Continue Using Product
```

Every future feature should improve this journey.

---

# CUSTOMER PORTAL

Purpose

Provide customers with a modern application that replaces wp-admin.

Customers should never need to use the WordPress dashboard.

---

Customer Dashboard

Displays

```
Welcome

Recent Tickets

Verified Purchases

Licenses

Downloads

Announcements

Notifications
```

The dashboard should become the customer's home.

---

# PURCHASES

Purpose

Display verified purchases.

Each purchase should include

```
Product

Provider

Purchase Date

License

Support Status

Support Expiry
```

Future

```
Renew Support

Upgrade License

View Invoice
```

---

# LICENSES

Purpose

Manage product licenses.

Future providers

```
LemonSqueezy

Freemius

EDD

WooCommerce
```

Capabilities

```
View License

Activate

Deactivate

Manage Domains

Regenerate
```

---

# DOWNLOADS

Purpose

Provide secure product downloads.

Each product should show

```
Latest Version

Previous Versions

Changelog

Release Date
```

Future

```
Beta Releases

Nightly Builds
```

---

# CUSTOMER PROFILE

Purpose

Manage customer identity.

Features

```
Avatar

Display Name

Email

Password

Notification Settings

Connected Providers
```

Future

```
Multiple Organizations

Multiple Team Members
```

---

# CUSTOMER NOTIFICATIONS

Customers should receive notifications about

```
Ticket Replies

Support Expiry

License Changes

Downloads

Announcements
```

Future

```
Slack

Discord

Telegram

Push Notifications
```

---

# TICKET EXPERIENCE

SupportBay should provide a modern support experience.

Ticket lifecycle

```
Create

↓

Assign

↓

Reply

↓

Resolve

↓

Close

↓

Reopen
```

Every ticket should contain

```
Customer

Verification

Messages

Attachments

Activities
```

---

# VERIFIED TICKETS

Every verified ticket references

```
Purchase Verification
```

Never

```
Purchase Code
```

Tickets remain provider-independent.

---

Ticket sidebar

Example

```
Verified Purchase

Product

License

Support Until

Related Tickets
```

The sidebar should summarize verification information.

---

# RELATED TICKETS

Every verification may have many tickets.

Example

```
Verification

↓

Ticket A

↓

Ticket B

↓

Ticket C
```

Customers and agents should easily navigate between related conversations.

---

# INTERNAL NOTES

Agents should be able to create

```
Private Notes
```

Visible only to

```
Agents

Managers

Administrators
```

Customers should never see internal notes.

---

# ATTACHMENTS

Attachments belong to Messages.

Supported

```
Images

PDF

ZIP

Video

Audio

Text

CSV

JSON
```

Future

```
Cloud Storage

Virus Scan

Image Compression
```

---

# SEARCH EXPERIENCE

SupportBay should provide global search.

Search

```
Tickets

Customers

Products

Providers

Verifications

Messages
```

Future

```
AI Semantic Search
```

---

# ADMIN EXPERIENCE

The administrator should control the entire platform.

Dashboard

↓

Tickets

↓

Customers

↓

Providers

↓

Reports

↓

Settings

↓

Logs

Everything should be accessible from one interface.

---

# PROVIDER MANAGEMENT

Administrators should configure providers without touching code.

Each provider should expose

```
Connection Status

Version

Settings

Credentials

Health Check
```

Future

```
Install Provider

Update Provider

Marketplace
```

---

# VERIFICATION MANAGEMENT

Administrators should manage

```
Verifications

Refresh

Reverify

Revoke

Snapshots
```

Verification history should remain permanent.

---

# REPORTING

SupportBay should eventually provide reports for

```
Tickets

Customers

Products

Providers

Verifications

Agents

Departments
```

Future

```
Charts

Forecasts

AI Insights
```

---

# KNOWLEDGE BASE

Purpose

Reduce ticket volume.

Features

```
Categories

Articles

Related Articles

Search

Featured Content
```

Future

```
AI Answer Suggestions

Automatic Recommendations
```

---

# PROVIDER ECOSYSTEM

SupportBay should support many provider types.

Marketplace

```
Envato

EDD

WooCommerce

Freemius

Paddle

LemonSqueezy

Gumroad
```

Payment

```
Stripe

PayPal

Paddle

LemonSqueezy
```

AI

```
OpenAI

Gemini

Claude
```

Storage

```
Amazon S3

Cloudflare R2

Backblaze

DigitalOcean Spaces
```

Notifications

```
Slack

Discord

Telegram

Microsoft Teams
```

Each provider should only implement Integration Contracts.

Core business modules must never change.

---

# PRODUCT PRINCIPLE

The product should feel like a SaaS application.

Even though SupportBay runs inside WordPress,

the customer experience should resemble

```
Linear

GitHub

Intercom

Help Scout

Zendesk
```

Modern

Fast

Clean

Responsive

---

# USER EXPERIENCE PRINCIPLES

Every screen should answer three questions.

```
Where am I?

↓

What needs attention?

↓

What should I do next?
```

Reduce clicks.

Reduce complexity.

Increase clarity.

---

# FUTURE PRODUCT DIRECTION

SupportBay should eventually become

```
Customer Success Platform

+

Support Platform

+

License Platform

+

AI Assistant

+

Knowledge Base

+

Automation Platform
```

The ticket system is only the foundation.

Every new feature should reinforce this long-term vision.

---

---

# BUSINESS STRATEGY

---

SupportBay is intended to become more than a software product.

It should become an ecosystem that allows developers, agencies, freelancers, SaaS companies, and digital marketplaces to manage their entire customer lifecycle from one platform.

The goal is not to compete only with WordPress support plugins.

The goal is to compete with complete customer support platforms.

Examples

```
Zendesk

Help Scout

Freshdesk

Intercom

Linear

GitHub Issues
```

while remaining deeply integrated with WordPress.

---

# TARGET AUDIENCE

SupportBay is designed for digital product businesses.

Primary Customers

```
WordPress Theme Authors

WordPress Plugin Authors

ThemeForest Authors

CodeCanyon Authors

Elementor Developers

Agencies

Freelancers
```

Secondary Customers

```
SaaS Companies

Digital Product Sellers

Software Companies

Online Education

Membership Platforms
```

Future Customers

```
Enterprise Teams

Large Support Departments

Multi-brand Businesses
```

---

# PRODUCT POSITIONING

SupportBay should position itself as

```
Customer Success Platform
```

instead of

```
Ticket Plugin
```

Customers should feel they are purchasing

Support

-

Verification

-

Licensing

-

Downloads

-

Customer Portal

-

AI Assistant

not merely

"another support plugin."

---

# COMPETITIVE ADVANTAGES

SupportBay differentiates itself through

Provider Independence

↓

Modern Architecture

↓

Customer Portal

↓

Marketplace Verification

↓

License Management

↓

AI Integration

↓

Extensibility

Unlike traditional plugins,

SupportBay should never be tightly coupled to one marketplace.

---

# ECOSYSTEM STRATEGY

SupportBay should evolve into multiple installable components.

Example

```
SupportBay Core

↓

SupportBay Envato

↓

SupportBay EDD

↓

SupportBay WooCommerce

↓

SupportBay Paddle

↓

SupportBay AI

↓

SupportBay Analytics

↓

SupportBay Knowledge Base
```

Every component should extend Core.

Core should remain stable.

---

# PROVIDER MARKETPLACE

Long-term vision

SupportBay should eventually support installing providers like apps.

Example

```
Install Provider

↓

Enable

↓

Configure

↓

Connect

↓

Use
```

Possible UI

```
Marketplace

Installed

Updates

Official

Third-party
```

Third-party developers should be able to publish providers.

---

# EXTENSION ECOSYSTEM

Future extension categories

```
Providers

Widgets

Reports

Automation

Notifications

Integrations

Themes

Portal Extensions
```

SupportBay should expose stable extension points.

---

# MARKETPLACE STRATEGY

Marketplace integrations

Current

```
Envato
```

Planned

```
Easy Digital Downloads

WooCommerce

Freemius

Paddle

LemonSqueezy

Gumroad

Shopify
```

Every marketplace should implement the same contracts.

Business modules should never change.

---

# AI STRATEGY

Artificial Intelligence should become a first-class capability.

SupportBay should never depend on one AI vendor.

Instead

```
AIProvider

↓

OpenAI

Gemini

Claude

Future Models
```

Capabilities

```
Suggested Replies

Ticket Summaries

Translation

Sentiment Analysis

Spam Detection

Priority Detection

Knowledge Base Search

AI Drafts
```

Future

```
Autonomous AI Agents

Workflow Automation

Ticket Routing

Conversation Memory
```

---

# AUTOMATION STRATEGY

SupportBay should support business automation.

Examples

```
When Ticket Created

↓

Assign Department

↓

Verify Purchase

↓

Notify Customer

↓

Create Activity

↓

Send Slack Message
```

Future

```
Visual Workflow Builder
```

similar to automation tools.

---

# KNOWLEDGE BASE STRATEGY

Knowledge Base should become deeply integrated.

Customer creates ticket

↓

SupportBay searches articles

↓

Suggests answers

↓

Customer resolves issue

↓

Ticket never created

Future

```
AI Search

Embeddings

Semantic Search
```

---

# ANALYTICS STRATEGY

SupportBay should provide meaningful business insights.

Reports

```
Ticket Volume

Response Time

Resolution Time

Support Load

Verification Statistics

Provider Health

Customer Growth

License Growth
```

Future

```
Predictive Analytics

AI Forecasting

Trend Detection
```

---

# MOBILE STRATEGY

SupportBay should expose a stable API.

Future clients

```
iOS

Android

Desktop

CLI

Third-party Apps
```

Business logic should remain inside Core.

---

# SAAS STRATEGY

The architecture should support SaaS in the future.

Current

```
Single WordPress Installation
```

Future

```
Multi Tenant

Organizations

Teams

Projects

Shared Resources
```

The current architecture should avoid decisions that make SaaS impossible.

---

# MULTI-ORGANIZATION VISION

Future customers may belong to organizations.

Example

```
Organization

↓

Projects

↓

Products

↓

Customers

↓

Agents

↓

Tickets
```

Current implementation should remain compatible with this future model.

---

# REVENUE MODEL

Potential revenue streams

```
SupportBay Pro

↓

Official Providers

↓

AI Features

↓

Premium Portal

↓

Analytics

↓

Automation

↓

Cloud Services
```

Optional future

```
Hosted SaaS

Managed SupportBay Cloud
```

---

# COMMUNITY STRATEGY

SupportBay should encourage community contributions.

Future

```
Public SDK

Documentation

Provider Development Kit

Extension API

Community Marketplace
```

The architecture should make third-party development straightforward.

---

# RELEASE STRATEGY

Major Releases

```
1.0

1.5

2.0

3.0
```

Minor Releases

```
Monthly Improvements
```

Patch Releases

```
Bug Fixes

Security

Performance
```

Architecture should prioritize backward compatibility.

---

# PERFORMANCE STRATEGY

Performance is a feature.

Goals

```
Minimal Database Queries

Lazy Loading

Dependency Injection

Provider Isolation

Efficient Indexes

Scalable Architecture
```

Avoid unnecessary abstractions that reduce performance.

---

# SECURITY STRATEGY

Security should be considered from day one.

Principles

```
Least Privilege

Input Validation

Output Escaping

Nonce Verification

Capability Checks

Token Hashing

Encrypted Secrets

Audit Logs
```

Never expose

OAuth Tokens

Secrets

Passwords

Full Purchase Codes

License Secrets

---

# LONG-TERM GOAL (2026–2030)

2026

```
Production Ready

Customer Portal

Envato

OAuth

Verification

REST API
```

2027

```
EDD

WooCommerce

Licenses

Downloads

Notifications

Knowledge Base
```

2028

```
OpenAI

Gemini

Claude

Automation

Analytics
```

2029

```
Provider Marketplace

Workflow Builder

Organizations

Teams

Enterprise Features
```

2030+

```
SupportBay Cloud

SaaS Platform

Public Marketplace

Mobile Applications

Enterprise Customer Success Platform
```

---

# SUCCESS PRINCIPLE

Every new feature should satisfy at least one of these goals.

```
Improve Customer Experience

Increase Extensibility

Reduce Coupling

Improve Maintainability

Increase Automation

Improve Performance

Increase Provider Independence
```

If a feature does not improve at least one of these areas, reconsider its inclusion.

---

---

# DEVELOPMENT CONSTITUTION

---

This section defines the non-negotiable principles of SupportBay.

These principles exist to ensure that the architecture remains clean, modular, maintainable, and scalable for many years.

Every contributor, whether human or AI, should follow these principles.

When a design decision conflicts with convenience, the architecture wins.

---

# THE GOLDEN RULE

SupportBay Core owns the business.

Providers own integrations.

Never mix them.

Examples

Correct

```
VerificationService

↓

PurchaseVerificationProvider

↓

EnvatoProvider
```

Incorrect

```
VerificationService

↓

Envato API
```

This rule must never be broken.

---

# NON-NEGOTIABLE PRINCIPLES

The following principles define the project.

## 1. Core Must Remain Provider Independent

Core modules should never import

```
Envato

EDD

WooCommerce

Freemius

Paddle

LemonSqueezy
```

Instead they depend on Integration Contracts.

If a module needs provider-specific logic, the architecture should be reconsidered.

---

## 2. Business Logic Lives in Services

Business rules belong only inside Services.

Never inside

```
Repositories

Entities

Controllers

Providers

Listeners
```

---

## 3. Database Access Lives in Repositories

Repositories are responsible only for persistence.

Repositories must never

- call APIs
- dispatch events
- validate business rules
- authenticate users

---

## 4. Entities Represent State

Entities should be as close to immutable as practical.

Entities may expose helper methods.

Examples

```
isOpen()

isVerified()

hasSnapshot()

canRefresh()
```

Entities must never modify the database.

---

## 5. Events Describe Facts

Events should describe

Something happened.

Examples

```
TicketCreated

VerificationVerified

CustomerRegistered
```

Events should never describe commands.

Incorrect

```
CreateTicket

SendEmail

RefreshProvider
```

---

## 6. Listeners Perform Side Effects

Listeners should remain small.

Typical responsibilities

```
Create Activity

Send Email

Clear Cache

Notify Slack

Refresh Analytics
```

Listeners should never become business workflows.

---

## 7. Providers Are Adapters

Providers translate external systems into SupportBay language.

They never become business modules.

Example

```
Envato Purchase

↓

PurchaseVerificationData

↓

VerificationService
```

Never

```
Envato

↓

Database

↓

Tickets
```

---

## 8. Every Module Owns Its Domain

A module owns

- Database
- Entity
- Repository
- Service
- Events
- Listeners
- Tests

Avoid leaking business logic between modules.

---

## 9. Every New Feature Needs a Home

Before writing code ask

Which module owns this feature?

If the answer is unclear,

stop and reconsider the design.

---

## 10. Every Workflow Should Be Testable

Every major workflow should have a Flow Test.

Examples

```
TicketFlowTest

AuthFlowTest

ProviderFlowTest

VerificationFlowTest
```

Future

```
OAuthFlowTest

CustomerPortalFlowTest

LicenseFlowTest

DownloadFlowTest
```

---

# ARCHITECTURAL DECISION RECORDS

Whenever a significant architectural decision is made, record it.

Recommended format

```
ADR-001

Decision

Reason

Alternatives

Consequences
```

Example

```
ADR-001

Purchase Verification is provider-independent.

Reason

Support multiple marketplaces without changing business modules.

Consequence

Providers normalize data through PurchaseVerificationData.
```

Future ADRs should live under

```
docs/adr/
```

---

# REFACTORING POLICY

Refactoring is encouraged when it improves

- readability
- maintainability
- extensibility

Refactoring should not

- rewrite stable modules unnecessarily
- introduce breaking changes without reason
- bypass established patterns

Improve architecture incrementally.

---

# PERFORMANCE PRINCIPLES

Performance should remain a design goal.

Preferred

```
Small Services

Lazy Loading

Indexed Queries

Efficient Repositories

Minimal Database Calls
```

Avoid

```
Repeated Queries

Unnecessary Abstractions

Large God Objects
```

---

# DOCUMENTATION POLICY

Documentation is part of the product.

Whenever architecture changes,

update

```
AGENTS.md

ROADMAP.md

MASTER_PLAN.md

Relevant README.md

Architecture Documents
```

The documentation should always describe the current architecture.

---

# VERSIONING STRATEGY

Semantic Versioning

```
Major

Minor

Patch
```

Major

Breaking architecture.

Minor

New features.

Patch

Bug fixes.

---

# BACKWARD COMPATIBILITY

SupportBay should prefer extending the architecture over replacing it.

Public APIs should remain stable whenever possible.

Migration paths should exist for major changes.

---

# DEVELOPMENT PHILOSOPHY

Before writing code ask

```
Does this belong here?

Will another provider need this?

Can this become reusable?

Does this increase coupling?

Does this fit the architecture?
```

If the answer is uncertain,

design first,

code second.

---

# PROJECT MANIFESTO

SupportBay is being built for the long term.

The goal is not to write the fastest code today.

The goal is to build a platform that remains maintainable five years from now.

Every module should feel intentional.

Every abstraction should have a purpose.

Every provider should be replaceable.

Every workflow should be understandable.

Every feature should strengthen the platform rather than complicate it.

Quality is measured by clarity, consistency, and maintainability—not by the number of features.

---

# CURRENT PROJECT STATUS

Foundation

```
100%
```

Business Modules

```
95%
```

Provider Architecture

```
95%
```

Verification Foundation

```
100%
```

Envato Integration

```
70%
```

Current Development Stage

```
PurchaseVerificationProvider
        ↓
PurchaseVerificationData
        ↓
EnvatoProvider Implementation
        ↓
VerificationService Provider-Orchestrated Verification
        ↓
ProviderVerificationFlowTest
        ↓
Ticket ↔ Verification Integration
        ↓
OAuth Login Flow
        ↓
Customer Portal
```

---

# IMMEDIATE NEXT STEPS

Sprint 1 (Current)

✅ Core Foundation

✅ Modules

✅ Verification Foundation

🚧 Provider-driven Verification

Current Tasks

```
1. Implement PurchaseVerificationProvider in EnvatoProvider

2. Convert EnvatoPurchase → PurchaseVerificationData

3. Add provider-driven verification orchestration to VerificationService

4. Create FakePurchaseProvider

5. Create ProviderVerificationFlowTest
```

Sprint 2

```
Ticket Verification

Related Tickets

Verification Sidebar

Ticket ↔ Verification Relationship
```

Sprint 3

```
OAuth Login

Customer Linking

Magic Login Improvements

Connected Providers
```

---

# FINAL PRINCIPLE

SupportBay should always remain

Simple

↓

Modular

↓

Predictable

↓

Provider Independent

↓

Extensible

↓

Maintainable

↓

Well Tested

If a future developer can understand the architecture by reading the code and documentation alone, then the project has succeeded.

---

End of MASTER_PLAN.md

Version: 1.0

Project:
SupportBay

Maintainers:
Tanvir Ahamed
OpenAI ChatGPT

Status:
Living document — update when major architectural decisions are approved.

---
