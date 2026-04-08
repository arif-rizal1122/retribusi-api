# Browser-Free Research Protocol

## Core Purpose
This skill establishes a strict methodology for performing research, data gathering, and verification within the M-PAD API environment while **prohibiting** the use of the Antigravity Browser Control.

## Prohibited Tools
> [!CAUTION]
> The following tools are **strictly forbidden** in this protocol:
> - `browser_subagent`
> - `read_browser_page`
> - `browser_scroll`, `browser_click`, `browser_type`, etc.

## Mandatory Alternatives
1. **Search & Discovery**:
   - `search_web` (cli-compatible summary only).
   - `read_url_content` (to fetch text/markdown directly).
   - `ls -R` and `ripgrep` for all local context discovery.
2. **Web Verification**:
   - `curl -svI <URL>` for health checks and headers.
   - `curl -s <URL> | grep <pattern>` for simple content verification.
3. **Internal Data Exploration**:
   - `list_dir`, `view_file`, and `grep_search`.
   - `read_url_content` for documentation URLs.

## Workflow Execution
1. **Context-First**: Always check local `docs/` and `.agent/skills/` first.
2. **Text-First Retrieval**: If external info is needed, use `read_url_content` (markdown format) instead of opening a browser.
3. **Systematic CLI Verification**: Verify API health and production links via `curl` in a `run_command` block.
4. **Research-First Validation**: Use the `research-first` methodology to prepare plans before coding, keeping evidence in text artifacts.

## Critical Guardrail
If a task *appears* to require a browser (e.g., visual layout verification), the agent must:
- Consult documentation (`docs/DOMAIN_SCHEMA.md`).
- Use `curl` to verify response status and content.
- Ask the user for manual verification if visual-only UI issues arise.
- **NEVER** fall back to the browser-subagent without explicit user override.
