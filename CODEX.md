You are continuing development of the SupportBay WordPress plugin.

Before writing or modifying any code, perform the following steps in order.

==============================================================================
STEP 1 — READ PROJECT DOCUMENTATION
==============================================================================

Read these files completely:

1. CURRENT_STATUS.md
2. AGENTS.md
3. ROADMAP.md
4. MASTER_PLAN.md

These documents are the source of truth for the project's architecture, coding standards, roadmap, and current milestone.

==============================================================================
STEP 2 — INSPECT THE REPOSITORY
==============================================================================

Do NOT assume the documentation is perfectly synchronized with the code.

Inspect the current repository and determine:

• Current architecture
• Existing modules
• Existing providers
• Current implementations
• Existing services
• Existing repositories
• Existing entities
• Existing flow tests

Use the actual code as the implementation source.

==============================================================================
STEP 3 — REPORT BEFORE CODING
==============================================================================

Before making any changes, report:

1. What is already implemented.

2. What is incomplete.

3. Any differences between the documentation and the code.

4. The exact files you intend to modify.

5. Why those files need modification.

Do NOT modify any code until this report is complete.

==============================================================================
STEP 4 — FOLLOW PROJECT ARCHITECTURE
==============================================================================

Always follow the existing architecture.

SupportBay follows:

• Dependency Injection
• Repository Pattern
• Entity Pattern
• Service Layer
• Event Driven Architecture
• Provider-based Integrations
• Flow Tests

Never bypass the architecture.

Never introduce shortcuts.

Never tightly couple business modules to providers.

==============================================================================
CURRENT DEVELOPMENT STAGE
==============================================================================

The current sprint is:

Provider-driven Purchase Verification.

Current target architecture:

VerificationService
↓
IntegrationManager
↓
PurchaseVerificationProvider
↓
Concrete Provider (Envato)
↓
PurchaseVerificationData
↓
Verification Entity
↓
Verification Repository

VerificationService must NEVER import Envato-specific classes.

==============================================================================
WORKING RULES
==============================================================================

Always:

✔ Keep modules provider-independent.

✔ Keep providers as adapters.

✔ Keep services responsible for business logic.

✔ Keep repositories responsible only for persistence.

✔ Keep entities immutable whenever practical.

✔ Keep events as business facts.

✔ Keep listeners small.

✔ Keep Flow Tests passing.

Never:

✘ Rewrite completed modules.

✘ Break Dependency Injection.

✘ Duplicate business logic.

✘ Import concrete providers into business modules.

✘ Introduce breaking architectural changes.

==============================================================================
WHEN THE TASK IS COMPLETE
==============================================================================

After implementation:

1. Summarize what changed.

2. List every modified file.

3. Explain why each change was necessary.

4. Report any remaining TODO items.

5. Update CURRENT_STATUS.md if the milestone has changed.

6. Update ROADMAP.md only after a major milestone or Flow Test has been completed.

==============================================================================
GENERAL RULE
==============================================================================

Always prefer understanding the existing architecture over generating new code.

If the current implementation conflicts with assumptions, follow the repository, explain the difference, and propose the smallest compatible solution.
