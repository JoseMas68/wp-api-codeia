# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**WP API Codeia** is an enterprise-grade WordPress plugin that transforms WordPress into a configurable, versioned, and governable headless API. It provides a visual admin dashboard for API management without requiring code changes.

**Current Status:** Early development phase. The plugin has basic structure and comprehensive architecture documentation, but implementation has not begun.

## Architecture Documentation

**CRITICAL:** Before making any architectural decisions, read `docs/ARQUITECTURA.md` (2437 lines). This document contains:

- Complete modular architecture with design patterns (Strategy, Factory, Repository, Observer, Middleware)
- Data models and JSON configuration schemas
- Authentication strategies (API Key, JWT, OAuth2, Basic Auth, Application Passwords)
- Permission matrix design
- Rewrite rules strategy
- Security and performance considerations
- Implementation roadmap with 4 phases

## Key Architectural Decisions

### Design Patterns

- **Strategy Pattern:** Authentication methods (API Key, JWT, OAuth2, etc.) are interchangeable strategies
- **Factory Pattern:** Creates endpoints and field detectors dynamically
- **Repository Pattern:** Abstracts data access from WordPress database
- **Observer Pattern:** Triggers documentation updates, cache invalidation on config changes
- **Middleware Pattern:** Request pipeline (CORS → Rate Limit → Auth → Permissions → Execution)

### Modular Structure

```
includes/
├── detectors/          # CPT, Taxonomy, ACF, JetEngine detection
├── endpoints/          # Endpoint management, versioning, rewrite rules
├── auth/              # Authentication strategies
├── permissions/       # Role, capability, endpoint, field-level permissions
├── media/             # Upload handling, validation
├── documentation/     # OpenAPI 3.0 generation
├── security/          # Rate limiting, CORS, sanitization
├── performance/       # Caching, lazy loading
├── logging/           # Request, error, auth logging
└── repositories/      # Data access layer
```

### Namespace and Prefix Conventions

- **PHP Namespace:** `WP_API_Codeia\`
- **Function Prefix:** `wp_api_codeia_`
- **Option Prefix:** `wp_api_codeia_`
- **Transient Prefix:** `wp_api_codeia_`
- **Database Table Prefix:** `wp_api_codeia_`

### API Endpoint Structure

```
/api/{version}/{endpoint_slug}
```

Examples:
- `/api/v1/posts`
- `/api/v2/posts` (versioned)
- `/api/v1/products`

### WordPress Hooks to Use

**Detection:**
- `init` (priority 999) - All post types registered
- `registered_post_type` - Post type registration
- `acf/update_field_group` - ACF changes

**Rewrite Rules:**
- `rewrite_rules_array` - Add dynamic rules
- `query_vars` - Register custom query vars
- `init` - Flush rewrite rules (admin only)

**Request Processing:**
- `rest_api_init` - Register REST routes (if using WP REST API)
- `parse_request` - Custom routing for rewrite rules

**Cache Invalidation:**
- `save_post` - Post updated
- `delete_post` - Post deleted
- `acf/update_value` - ACF field updated

## Configuration Model

The plugin uses a JSON-based configuration stored in WordPress options or custom tables:

```json
{
  "version": "1.0.0",
  "global_settings": { ... },
  "endpoints": { ... },
  "auth": { ... },
  "permissions": { ... }
}
```

Configuration is versioned and includes automatic migrations on plugin updates.

## Implementation Roadmap

**Phase 1 (MVP):** Basic CPT detection, simple endpoints, API Key auth, basic permissions, OpenAPI generation

**Phase 2:** ACF/JetEngine detection, JWT auth, field-level permissions, rate limiting, caching, media upload

**Phase 3:** Endpoint versioning, custom query params, Swagger UI, advanced logging, metrics dashboard

**Phase 4 (Enterprise/PRO):** OAuth2, webhooks, multi-tenant, advanced rate limiting, analytics

## Development Commands

*Note: No build, test, or lint commands are configured yet. This will be updated as the project progresses.*

## Important Constraints

- **WordPress Version:** 6.x
- **PHP Version:** 8.2+
- **License:** GPL v2 or later
- **Multisite:** Must support WordPress multisite
- **ACF/JetEngine:** Must gracefully handle when these plugins are not active
- **Backward Compatibility:** API versions must be supported for 12 months
- **Performance:** Use transients, object cache, and lazy loading

## Security Considerations

- All inputs must be sanitized using WordPress functions
- Use `wp_verify_nonce()` for admin forms
- API keys must be hashed using `password_hash()`
- JWT secrets must be generated on installation
- Never expose user enumeration endpoints
- Implement rate limiting per endpoint/API key/user
- Field-level permissions for sensitive data

## Testing Strategy

- **Unit Tests:** PHPUnit for detectors, auth strategies, permissions
- **Integration Tests:** WordPress test suite for endpoint flows
- **E2E Tests:** Codeception/Playwright for admin dashboard
- **Load Tests:** Apache Bench/JMeter for performance testing

## Documentation Standards

- All public methods must have PHPDoc blocks
- Complex logic must include inline comments explaining the "why"
- Schema changes must update `docs/ARQUITECTURA.md`
- New features must update the implementation roadmap

## When Adding New Features

1. Check `docs/ARQUITECTURA.md` for the relevant module
2. Follow the established design patterns
3. Add WordPress hooks as specified in the architecture
4. Update permission matrix if needed
5. Consider ACF/JetEngine integration
6. Plan for multisite compatibility
7. Add logging for debugging
8. Update OpenAPI documentation generation
