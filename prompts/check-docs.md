# Documentation Consistency Checker

## Context
Act as a Senior Technical Editor and Quality Assurance Specialist with expertise in technical documentation, logic, and linguistic precision.
Your task is to verify ALL project documentation for consistency and correctness.

## Your Mission
Before starting ANY task in this project, first check documentation for contradictions and errors.

## Step 1: Auto-Discover All Documentation Files

Run these commands to find ALL documentation files:

```bash
find docs/ -name "*.md" -type f
find docs/ -name "*.yaml" -type f
ls -la .config/agents/
ls -la .github/
```

Read every file found:
- All `.md` files in `docs/adr/`
- All `.md` files in `docs/guides/`
- All `.md` files in `docs/architecture/`
- `docs/MERCURE.md`, `docs/openapi.yaml`
- `.config/agents/agents.md`
- `.config/agents/agents.local.md` (if exists)
- `AGENTS.md`, `README.md`
- `.github/CONTRIBUTING.md`

## Step 2: Checklist for Each File

### Basic Checks
- [ ] No broken internal links
- [ ] No broken code examples
- [ ] PHP 8.5, Symfony 7.x, FrankenPHP mentioned correctly
- [ ] No placeholder text like "TODO" or "FIXME"

### Cross-File Consistency Checks

#### 1. Folder Structure
Verify all documents describe the same structure:
- [ ] `src/` organization (Features vs UseCase vs Domain/Application/UI)
- [ ] Shared folder location (`src/Shared/` vs `src/{Module}/Shared/`)
- [ ] EntryPoint naming (Controller vs Action)

#### 2. Terminology
Check for naming conflicts:
- [ ] Message vs Command vs Query
- [ ] Handler vs Action
- [ ] Features vs UseCase

#### 3. Commands
- [ ] Makefile commands match documented commands
- [ ] Docker commands are consistent

#### 4. Language
- [ ] Code comments in English
- [ ] Documentation in English

## Step 3: Output Format

### If contradictions found:
```
## Found Contradictions

| File | Line | Issue | Contradicts With |
|------|------|-------|------------------|
| README.md | 89 | Uses `src/Features/` | AGENTS.md uses `src/UseCase/` |

Resolution needed: Choose one structure as canonical.
```

### If no contradictions:
```
✅ Documentation verified. No contradictions found.
```

## Step 4: After Audit

- Contradictions found → STOP, report them, ask user which version is correct
- All clear → Proceed with your task
