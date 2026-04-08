# Specialized Agents

The M-PAD API agentic ecosystem uses a delegation-first architecture. For complex tasks, the following subagents are invoked to ensure high-quality outcomes.

## Core Agents

### Planner (The Strategist)
- **Role**: Breaks down complex user requests into multi-phase implementation plans.
- **Trigger**: New features, architectural changes, or significant refactors.
- **Skill**: Domain knowledge of M-PAD's 4-tier hierarchy and regional tax logic.

### Architect (The System Designer)
- **Role**: Ensures code consistency with Laravel 11, PHP 8.2+, and PostgreSQL/MySQL schemas.
- **Focus**: Migrations, API Resources, Service/Action patterns, and Eloquent model integrity.
- **Protocol**: Standardizes API responses and validates data-flow across related services.

### Reviewer (The Quality Gate)
- **Role**: Enforces strict code quality (PSR-12) and testing (PHPUnit/Pest).
- **KPI**: 80%+ test coverage, Pint-clean code, and no dead logic.
- **Trigger**: Before finalizing any PR or major commit.

### Auditor (The Compliance Officer)
- **Role**: Validates business logic against **Perwali Kota Baubau** and Bapenda's regulatory requirements.
- **Focus**: Calculation accuracy for Pajak Reklame, PBB-P2, and PBJT.
- **Reference**: `docs/MASTER_KNOWLEDGE_BASE.md`.

## Integration Protocol
- Agents communicate primarily via updated `.md` artifacts (`implementation_plan.md`, `task.md`, `walkthrough.md`).
- Every major change must pass through **Planner** -> **Architect** -> **Reviewer** -> **Auditor** phases as appropriate.
- **Direct Browser Control is prohibited** for all agents unless absolutely no CLI/URL alternative exists.
