# Agent Setup Guide

This guide explains how to configure AI agents (Cursor, Windsurf, Copilot) to work with this project.

## Files Overview

| File | Purpose | Tracked |
|------|---------|---------|
| `AGENTS.md` | Core project rules for AI agents | ✅ |
| `SYSTEM_PROMPT.md` | Prompt template for context-aware agents | ✅ |
| `agents.local.md` | Your personal local settings | ❌ (gitignored) |

## Quick Setup

### Step 1: Copy Local Settings Template

```bash
cp agents.local.md.example agents.local.md
```

### Step 2: Configure Your Environment

Edit `agents.local.md`:

```markdown
## Environment & Tools
- **Git CLI Path**: /usr/bin/git (your path here)
- **Local Workspace**: /path/to/your/project
- **Preferred Shell**: zsh
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

## Recommended Configuration

### Cursor Custom Instructions

Add to Cursor settings:

```
You are working on a Pragmatic Franken project.
First, read SYSTEM_PROMPT.md for context-aware instructions.
Then, read AGENTS.md for core architectural rules.
If agents.local.md exists, use it for your local environment.
```

### Shell Alias (Optional)

```bash
# Add to ~/.zshrc or ~/.bashrc
alias agents-refresh='cat AGENTS.md SYSTEM_PROMPT.md agents.local.md 2>/dev/null'
```

## Troubleshooting

### Agent Ignoring Project Rules

1. Ensure `SYSTEM_PROMPT.md` is loaded in editor context
2. Check `agents.local.md` exists and is valid markdown
3. Restart your AI editor

### Git Commands Not Found

Update `agents.local.md` with correct Git path:

```markdown
- **Git CLI Path**: $(which git)
```

### Missing Context

Ensure files are in root directory:
- `AGENTS.md`
- `SYSTEM_PROMPT.md`
- `agents.local.md` (your copy)

## Best Practices

1. **Keep secrets out**: Never add API keys to any `.md` files
2. **Refresh regularly**: Run `git pull` to get latest `AGENTS.md`
3. **Share rules**: Update `AGENTS.md` for team-wide rules
4. **Local only**: Use `agents.local.md` for your personal settings
