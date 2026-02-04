# AI Agents and Automation Guide

This guide covers free AI tools and automation to maintain repository health.

## 🚀 Free AI Tools

### 1. CodiumAI (PR-Agent)

CodiumAI offers a free plan for automated PR reviews.

**Features:**
- Auto-generate PR descriptions
- Code analysis and bug detection
- Security checks
- Test generation suggestions

**Setup:**

1. Visit [CodiumAI PR-Agent](https://github.com/codiumai/pr-agent/)
2. Install the GitHub App (free tier available)
3. Configure via `.github/workflows/pr-agent.yml`:

```yaml
name: PR Agent

on:
  pull_request:
    types: [opened, review_requested]
  issue_comment:
    types: [created]

permissions:
  contents: read
  pull-requests: write
  issues: write

jobs:
  pr_agent:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: codiumai/pr-agent@main
        env:
          OPENAI_KEY: ${{ secrets.OPENAI_API_KEY }}
          # Or use Anthropic, Gemini, etc.
```

**Usage:**
- `/describe` - Auto-generate PR description
- `/review` - Code review
- `/improve` - Suggest improvements
- `/test` - Generate tests

---

### 2. GitHub Copilot Extensions

Copilot can answer questions directly in Issues.

**Setup:**

1. Enable Copilot in repository settings
2. Create `.github/copilot-instructions.md`:

```markdown
# Copilot Instructions

You are helping with the Pragmatic Franken project.

## Project Context
- PHP 8.5 with FrankenPHP and Symfony
- DDD Architecture with Modular Monolith
- Use Strict Types and readonly classes

## Answer Style
- Be concise
- Provide code examples
- Reference docs when relevant
```

---

### 3. Google Gemini via GitHub Actions

Automate issue description quality checks.

**Setup:**

Create `.github/workflows/issue-gemini-check.yml`:

```yaml
name: Issue Quality Check

on:
  issues:
    types: [opened, edited]

permissions:
  contents: read
  issues: write

jobs:
  gemini_check:
    runs-on: ubuntu-latest
    if: contains(github.event.issue.labels.*.name, 'needs-review') == false
    steps:
      - name: Gemini Analysis
        run: |
          # Simple prompt to Gemini API
          ISSUE_BODY="${{ github.event.issue.body }}"
          
          # Call Gemini API (example)
          curl -X POST "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent" \
            -H "Content-Type: application/json" \
            -d "{
              'contents': [{
                'parts': [{
                  'text': 'Analyze this issue. If it lacks detail, respond with \"NEEDS_MORE_INFO\". Otherwise, respond with \"READY\". Issue: ${ISSUE_BODY}'
                }]
              }]
            }" \
            -o response.json
          
          # Post comment if needs more info
          if grep -q "NEEDS_MORE_INFO" response.json; then
            gh issue comment ${{ github.event.issue.number }} \
              --body "Thanks for opening this issue! To help us respond faster, could you add more details about..."
          fi
        env:
          GEMINI_API_KEY: ${{ secrets.GEMINI_API_KEY }}
```

---

### 4. Dependabot

Automatically update dependencies.

**Setup:**

Create `.github/dependabot.yml`:

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependencies"
      - "composer"
    commit-message:
      prefix: "chore"
      prefix-development: "chore"
      include: "all"

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependencies"
      - "github-actions"

  - package-ecosystem: "docker"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependencies"
      - "docker"
```

**Additional Configuration:**

```yaml
# Auto-merge for minor/patch updates
reviewers:
  - team
automerge: true
automerge-method: squash
automerge-strategy: fast-forward
```

---

## 📋 Recommended Automation Stack

| Tool | Purpose | Cost |
|------|---------|------|
| Dependabot | Dependency updates | Free |
| CodiumAI | PR reviews | Free tier |
| GitHub Copilot | Issue assistance | Paid/Free trial |
| GitHub Actions | Custom workflows | Free minutes |

---

## 🔧 Quick Setup Commands

### Enable Dependabot

```bash
# Already configured - just enable in GitHub:
# Repository Settings → Code security → Dependabot
```

### Add Secrets

Navigate to: Repository → Settings → Secrets and variables → Actions

Add:
- `OPENAI_API_KEY` (for CodiumAI)
- `GEMINI_API_KEY` (for Gemini workflow)

---

## 📚 Additional Resources

- [CodiumAI Documentation](https://github.com/codiumai/pr-agent/)
- [Dependabot Configuration](https://docs.github.com/en/code-security/dependabot/dependabot-version-updates/configuration-options)
- [GitHub Actions](https://docs.github.com/en/actions)
