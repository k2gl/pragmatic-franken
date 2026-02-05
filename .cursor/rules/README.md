# IDE Rules for Pragmatic Franken

This folder contains enforcement rules for AI-powered IDEs (Cursor, Windsurf).

## Purpose

These rules ensure:
- Vertical Slices architecture is maintained
- CQRS pattern is followed
- Code coupling stays low

## Usage

### Cursor
1. Open Cursor → Settings → Rules
2. Add rules from this folder

### Windsurf
1. Open Windsurf → Settings → AI Rules
2. Reference these files

## Files

| Rule | Purpose |
|------|---------|
| `vertical-slices.rule` | Enforce Features/ folder structure |
| `cqrs.rule` | Enforce Command/Query separation |
| `no-spaghetti.rule` | Prevent coupling violations |

## For Developers

These rules protect your project from:
- Junior developers creating "Services" folders
- AI assistants generating spaghetti code
- Accidental architectural drift

Run `check-docs` prompt regularly to validate architecture compliance.
