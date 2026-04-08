# 🚫 GLOBAL BROWSER-FREE PROTOCOL (Priority: High)

## Enforcement
This rule applies to **every** interaction within the `retribusi-api` workspace.

## Primary Constraint
The **Antigravity Browser Control** suite (subagents and visual tools) is **DISABLED** by default.

### 🛑 NEVER USE
- `browser` or `browser_subagent`
- `read_browser_page`
- `browser.click()`, `browser.type()`, etc.

### ✅ ALWAYS USE
1. **Research & Fulfilling**: Use `read_url_content`, `run_command` (curl/wget), and `grep_search`.
2. **Local Verification**: Execute test scripts in `testing/` or `tests/`.
3. **External Discovery**: `search_web` (cli mode).
4. **Manual Validation**: Ask the user to verify UI changes or deployments that require visual checks.

## Rationale
- **Performance**: CLI/Text-based retrieval is significantly faster and less resource-intensive.
- **Reliability**: Automation via scripts and text extraction is more robust than visual interaction.
- **Focus**: The M-PAD API is a headless backend system; visual browser interaction is rarely necessary for code-centric tasks.

## Protocol Implementation
If a task *appears* to require the browser, the agent MUST explicitly state the limitation and offer a CLI-based alternative or request a manual check.
**NO EXCEPTIONS** unless explicitly requested by the user for a specific session.
