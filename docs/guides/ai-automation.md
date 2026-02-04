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

### 3. Free Issue Quality Checks (No API Key)

Automate issue description checks without external AI.

**Setup:**

Create `.github/workflows/issue-quality-check.yml`:

```yaml
name: Issue Quality Check

on:
  issues:
    types: [opened, edited]

permissions:
  contents: read
  issues: write

jobs:
  check_issue:
    runs-on: ubuntu-latest
    steps:
      - name: Check Issue Quality
        id: quality
        run: |
          BODY="${{ github.event.issue.body }}"
          LENGTH=${#BODY}

          if [ $LENGTH -lt 50 ]; then
            echo "quality=low" >> $GITHUB_OUTPUT
          elif [ $LENGTH -lt 200 ]; then
            echo "quality=medium" >> $GITHUB_OUTPUT
          else
            echo "quality=high" >> $GITHUB_OUTPUT
          fi

      - name: Comment on Low Quality Issues
        if: steps.quality.outputs.quality == 'low'
        uses: actions/github-script@v7
        with:
          script: |
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: 'Thanks for opening this issue! To help us respond faster, could you add more details about:\n- What were you trying to do?\n- What happened?\n- Expected behavior?\n\nA good description helps us fix bugs faster! 🐛'
            })

      - name: Add needs-review label
        if: steps.quality.outputs.quality != 'high'
        run: |
          gh issue edit ${{ github.event.issue.number }} --add-label "needs-review"
```

---

### 4. Auto-Label New Issues (No API Key)

Automatically label new issues for better organization.

**Setup:**

Create `.github/workflows/auto-label-issues.yml`:

```yaml
name: Auto Label Issues

on:
  issues:
    types: [opened]

permissions:
  contents: read
  issues: write

jobs:
  label_issues:
    runs-on: ubuntu-latest
    steps:
      - name: Add default label
        run: |
          # Add 'needs-review' label to all new issues
          gh issue edit ${{ github.event.issue.number }} --add-label "needs-review"

      - name: Check for bug keywords
        if: contains(github.event.issue.title, 'bug') || contains(github.event.issue.title, 'Bug')
        run: |
          gh issue edit ${{ github.event.issue.number }} --add-label "bug" --remove-label "needs-review"

      - name: Check for feature keywords
        if: contains(github.event.issue.title, 'feature') || contains(github.event.issue.title, 'Feature') || contains(github.event.issue.title, 'request')
        run: |
          gh issue edit ${{ github.event.issue.number }} --add-label "enhancement" --remove-label "needs-review"

      - name: Check for documentation keywords
        if: contains(github.event.issue.title, 'docs') || contains(github.event.issue.title, 'documentation')
        run: |
          gh issue edit ${{ github.event.issue.number }} --add-label "documentation" --remove-label "needs-review"
```

---

### 5. Optional: Google Gemini (Requires API Key)

For advanced AI-powered issue analysis, you can use Gemini.

**Setup (Requires API Key):**

Create `.github/workflows/issue-gemini-check.yml`:

```yaml
# OPTIONAL: Requires GEMINI_API_KEY secret
# Skip this section if you don't have an API key

name: Gemini Issue Analysis

on:
  issues:
    types: [opened, edited]

permissions:
  contents: read
  issues: write

jobs:
  gemini_analysis:
    runs-on: ubuntu-latest
    if: ${{ secrets.GEMINI_API_KEY != '' }}
    steps:
      - name: Gemini Analysis
        run: |
          ISSUE_BODY="${{ github.event.issue.body }}"

          # Call Gemini API (example structure)
          curl -X POST "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent" \
            -H "Content-Type: application/json" \
            -d "{
              'contents': [{
                'parts': [{
                  'text': 'Analyze this issue briefly. If it lacks detail, respond with \"NEEDS_MORE_INFO\". Otherwise, respond with \"READY\". Issue: '${ISSUE_BODY}'
                }]
              }]
            }" \
            -o response.json

          if grep -q "NEEDS_MORE_INFO" response.json; then
            gh issue comment ${{ github.event.issue.number }} \
              --body "Thanks! To help us understand better, could you add more details about..."
          fi
        env:
          GEMINI_API_KEY: ${{ secrets.GEMINI_API_KEY }}
```

---

### 6. Dependabot

Automatically update dependencies.

**Setup:**

Create `.github/dependabot.yml`:

```yaml
version: 2
updates:
  # Composer (PHP dependencies)
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
    labels:
      - "dependencies"
      - "composer"

  # GitHub Actions
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependencies"
      - "github-actions"

  # Docker
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
automerge: true
automerge-method: squash
automerge-strategy: fast-forward
```

---

## 📋 Recommended Automation Stack

| Tool | Purpose | Cost | API Key Required |
|------|---------|------|------------------|
| Dependabot | Dependency updates | Free | No |
| CodiumAI | PR reviews | Free tier | Yes (optional) |
| Issue Quality Check | Issue validation | Free | No |
| Auto-Label Issues | Auto-labeling | Free | No |
| Gemini | AI analysis | Paid/Free tier | Yes |

---

## 🔧 Quick Setup

### Enable Dependabot

Already configured - just enable in GitHub:
- Repository → Settings → Code security → Dependabot

### Add Secrets (Optional)

Navigate to: Repository → Settings → Secrets and variables → Actions

Only add if using CodiumAI or Gemini:
- `OPENAI_API_KEY` (for CodiumAI)
- `GEMINI_API_KEY` (for Gemini - optional)

---

## 📚 Additional Resources

- [CodiumAI Documentation](https://github.com/codiumai/pr-agent/)
- [Dependabot Configuration](https://docs.github.com/en/code-security/dependabot/dependabot-version-updates/configuration-options)
- [GitHub Actions](https://docs.github.com/en/actions)
- [Conventional Commits](https://www.conventionalcommits.org/)
