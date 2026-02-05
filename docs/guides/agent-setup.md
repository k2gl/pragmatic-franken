# Agent Setup Guide

This guide explains how to configure AI agents (Cursor, Windsurf, Copilot) to work with this project.

## Files Overview

| File | Purpose | Tracked |
|------|---------|---------|
| `.config/agents/agents.md` | Core project rules for AI agents | ✅ |
| `.config/agents/agents.local.md.example` | Template for local settings | ✅ |
| `.config/agents/agents.local.md` | Your personal local settings | ❌ (gitignored) |
| `SYSTEM_PROMPT.md` | Prompt template for context-aware agents | ✅ |

## Directory Structure

`````
.config/agents/
├── agents.md              ← Core rules (tracked)
├── agents.local.md.example ← Template (tracked)
└── agents.local.md        ← Personal settings (gitignored)
```

## Quick Setup

### Step 1: Copy Local Settings Template

```bash
cp .config/agents/agents.local.md.example .config/agents/agents.local.md
```

### Step 2: Configure Your Environment

Edit `.config/agents/agents.local.md`:

```markdown
## Environment: Tool Paths
- **GIT_CLI_PATH**: "/usr/local/bin/git"
- **SHELL_TYPE**: "zsh"

## Personal Preferences
- **Tone**: "Professional, concise"
- **COMMIT_STYLE**: "Conventional Commits"
```

### Step 3: Configure Your AI Editor

#### Cursor

1. Open Cursor → Settings → AI → General
2. Set "Custom Instruction File" to:
   - `SYSTEM_PROMPT.md` (recommended)

#### Windsurf

1. Open Windsurf → Settings → AI
2. Add `SYSTEM_PROMPT.md` to context

#### GitHub Copilot

1. Create `.github/copilot-instructions.md`
2. Reference `SYSTEM_PROMPT.md` content

## Configuration Categories

### 1. Environment & Tooling
- Hardware limits (laptop vs server)
- OS-specific settings (WSL2, macOS, Linux)
- Custom proxy configurations

### 2. Git Workflow Automation
- Commit message format
- Pre-push checks
- Auto-branch naming conventions

### 3. Code Quality Standards
- SOLID principles
- TypeScript strictness
- Comment policy
- Preferred libraries

### 4. Safety & Protection
- Protected branches
- Destructive command confirmations
- Hidden folder patterns

### 5. Shadow Mode (Smart Context)
- Reading `.env` without exposing values
- Pattern-based file skipping

## Recommended Configuration

### Cursor Custom Instructions

Add to Cursor settings:

```
You are working on a Pragmatic Franken project.
First, read SYSTEM_PROMPT.md for context-aware instructions.
Then, read .config/agents/agents.md for core architectural rules.
If .config/agents/agents.local.md exists, use it for your local environment.
```

### Shell Alias (Optional)

```bash
# Add to ~/.zshrc or ~/.bashrc
alias agents-refresh='cat .config/agents/agents.md SYSTEM_PROMPT.md .config/agents/agents.local.md 2>/dev/null'
```

## Troubleshooting

### Agent Ignoring Project Rules

1. Ensure `SYSTEM_PROMPT.md` is loaded in editor context
2. Check `.config/agents/agents.local.md` exists and is valid markdown
3. Restart your AI editor

### Git Commands Not Found

Update `.config/agents/agents.local.md` with correct Git path:

```markdown
- **GIT_CLI_PATH**: "$(which git)"
```

### Missing Context

Ensure files exist:
- `.config/agents/agents.md`
- `SYSTEM_PROMPT.md`
- `.config/agents/agents.local.md` (your copy)

## Best Practices

1. **Keep secrets out**: Never add API keys to any `.md` files
2. **Refresh regularly**: Run `git pull` to get latest `.config/agents/agents.md`
3. **Share rules**: Update `.config/agents/agents.md` for team-wide rules
4. **Local only**: Use `.config/agents/agents.local.md` for your personal settings
