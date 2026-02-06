# Notification Banner Module - Development Guide

This document describes the architecture and conventions for the OpenCoreEMR Notification Banner module.

## Module Overview

This module displays a notification banner at the top of all OpenEMR pages when configured. Use cases include site maintenance announcements and other system-wide messages.

- **Namespace:** `OpenCoreEMR\Modules\NotificationBanner`
- **Package:** `opencoreemr/oce-module-notification-banner`
- **PHP:** 8.2+ required

## Architecture

This is a simple event-based module with no public entry points:

```
oce-module-notification-banner/
├── src/
│   ├── Bootstrap.php      # Event subscriptions, Twig setup
│   └── GlobalConfig.php   # Module settings wrapper
├── templates/
│   └── notification-banner.html.twig
├── openemr.bootstrap.php  # Module loader
├── info.txt               # Module display name (REQUIRED)
└── composer.json
```

### Key Components

| File | Purpose |
|------|---------|
| `Bootstrap.php` | Subscribes to `GlobalsInitializedEvent` and `RenderEvent` |
| `GlobalConfig.php` | Wraps `$GLOBALS` access for module settings |
| `notification-banner.html.twig` | Banner template rendered on every page |

### Event Flow

1. `openemr.bootstrap.php` instantiates `Bootstrap` and calls `subscribeToEvents()`
2. `GlobalsInitializedEvent` registers admin settings
3. `RenderEvent::EVENT_BODY_RENDER_PRE` renders the banner (if active)

## Configuration

Configure in **Administration > Globals > OpenCoreEMR Notification Banner**:

| Setting | Key | Description |
|---------|-----|-------------|
| Activate Notification Banner | `oce_notification_banner_active` | Toggle banner display |
| Notification Banner Message | `oce_notification_banner_message` | Message text to display |

## Development

### Quick Reference

```bash
composer install           # Install dependencies
composer phpcs             # Code style check
composer phpcbf            # Code style auto-fix
composer phpstan           # PHPStan static analysis
composer code-quality      # Run all checks
composer require-checker   # Check for undeclared dependencies
```

### Code Quality Standards

All code must pass these checks before committing:

```bash
composer code-quality
```

This runs PHPCS, PHPStan, and Rector.

## Module info.txt (REQUIRED)

**Every module MUST have an `info.txt` file.** OpenEMR reads this file to display the module name in the admin UI.

Format: Single line with the display name (e.g., `OpenCoreEMR Notification Banner`). If missing, OpenEMR falls back to the directory name.

## Versioning with Release Please

Module versions are managed automatically by Release Please. **Never edit version numbers manually.**

- `.release-please-manifest.json` - Source of truth for version
- `version.php` - Updated automatically via `extra-files` in release-please-config.json
- Merge PRs with conventional commit titles; Release Please handles the rest

## CRITICAL: Handling Errors and Warnings

**NEVER ignore errors or warnings from any check.** Make every effort to fix them properly.

**Forbidden shortcuts (require explicit user approval):**
- Adding entries to `symbol-whitelist` in `.composer-require-checker.json`
- Adding entries to a PHPStan baseline file
- Using `@phpstan-ignore-*` annotations
- Using `// phpcs:ignore` comments
- Suppressing warnings with `@SuppressWarnings`

If suppression seems genuinely necessary, **ask the user first** and explain why it cannot be fixed properly.

**The right approach:**
1. Understand what the error is telling you
2. Fix the root cause (add missing types, fix logic, add dependencies)
3. If stuck, ask the user for guidance
4. Only suppress with explicit user approval and a comment explaining why

## CI Checks

### Conventional Commit Titles

PR titles must follow conventional commits with **lowercase subject**:

```
type: lowercase description
```

Examples:
- `fix: resolve phpstan errors` (correct)
- `feat: add dismiss button` (correct)
- `fix: Resolve PHPStan errors` (WRONG - uppercase)

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`, `deps`

### Composer Require Checker

CI runs `composer-require-checker` to verify all used symbols are declared.

**When using new OpenEMR classes:** The `symbol-whitelist` in `.composer-require-checker.json` contains OpenEMR classes provided at runtime. **Ask the user before adding new entries.**

**When using PHP extensions**, add to `composer.json`:

```json
{
  "require": {
    "ext-ctype": "*"
  }
}
```

## Conventions

- Follow the parent `CLAUDE.md` rules (active voice comments, no `empty()`, `?type` not `type|null`, etc.)
- File headers: `@author Michael A. Smith <michael@opencoreemr.com>`, `@copyright Copyright (c) {year} OpenCoreEMR Inc`
- Conventional commits: `feat:`, `fix:`, `docs:`, etc.
- No `Co-Authored-By` lines in commits
