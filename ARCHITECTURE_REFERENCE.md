# SupportBay Architecture Reference

## Reference Plugins

### support-genix-lite (/Users/itanvir/Downloads/support-genix-lite)
- **Core Structure**:
  - api/ (REST API endpoints)
  - appcore/ (core application logic)
  - assets/ (frontend assets)
  - blocks/ (React components)
  - core/ (main application logic)
  - libs/ (shared utilities)
  - models/ (data models)
  - modules/ (business logic modules)
  - templates/ (UI templates)
  - traits/ (shared traits)
  - views/ (UI views)
  - support-genix-lite.php (plugin entry point)
  - support-genix-lite.php (main plugin file)

### support-genix-pro (/Users/itanvir/Downloads/support-genix-pro)
- **Core Structure**:
  - api/ (REST API)
  - appcore/ (core application)
  - css/ (CSS styles)
  - images/ (images)
  - js/ (JavaScript)
  - libs/ (shared utilities)
  - models/ (data models)
  - modules/ (business logic modules)
  - template/ (UI templates)
  - tmp/ (temporary files)
  - uilib/ (UI components)
  - views/ (UI views)
  - support-genix.php (plugin entry point)

## Key Architecture Patterns

1. **Modular Structure**: Both use dedicated modules for each business capability
2. **Separation of Concerns**: Clear separation between data, business logic, and presentation
3. **Dependency Management**: Proper use of dependency management systems
4. **Frontend Organization**: Dedicated directories for UI components and assets

## SupportBay Alignment
- SupportBay already follows similar patterns with:
  - 22 distinct modules (Tickets, Messages, etc.)
  - assets/src/ for React frontend
  - Core/ directory for main application logic
  - Providers/ module for integration patterns
  - Comprehensive Flow Testing system

## Future Integration Guidance
1. **Maintain Module Boundaries**: Continue module-per-responsibility approach
2. **Preserve Frontend Structure**: Continue React/TypeScript organization in assets/
3. **Follow Provider Pattern**: Use Providers module for new integrations
4. **Continue Testing**: Maintain comprehensive Flow Test coverage