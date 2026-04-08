# ECC Universal Principles & Coding Style

## Core Methodology
- **Research-First**: Complex changes require deep analysis of existing schemas as documented in `.agent/skills/`.
- **Plan-Phase-Execute**: Use `implementation_plan.md` for major changes and `task.md` for execution tracking.
- **Walkthrough**: Every significant change MUST include a `walkthrough.md` with verification results.

## Coding Standards (Common)
- **Immutability**: Prefer creating new objects/DTOs over mutating existing state.
- **Naming**: Use descriptive, intention-revealing names. No single-letter variables except in simple loops.
- **Functionality**: Keep methods small (< 20 lines) and focused (SRP).
- **Security**: Sanitize all inputs and use prepared statements for all database interactions.

## Git Workflow
- **Commit Messages**: Use Conventional Commits (`feat:`, `fix:`, `refactor:`, `docs:`).
- **PR Readiness**: Lint via Pint and run tests before considering code complete.
- **Branching**: Follow Bapenda's `Git-Flow` as defined in `docs/SYSTEM_OVERVIEW.md`.

## Verification Protocol
- **Local First**: Run `testing/test_api_crud.sh` regularly.
- **No Browser**: Verify API endpoints via `curl` and unit tests.
- **Logs**: Monitor `storage/logs/laravel.log` during development.

## Documentation
- **Consistency**: Update `docs/` if architecture or features change.
- **Knowledge Item (KI)**: Create or update KIs for new business logic patterns.
