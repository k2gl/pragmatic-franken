# Pragmatic Franken Roadmap 2026

> **"Stop refactoring. Start delivering."**

## Vision

Pragmatic Franken evolves from a development foundation into a complete "Cheat Code" for modern PHP development. The roadmap focuses on removing friction, adding AI autonomy, and optimizing for edge performance.

---

## 🟦 Q1: Real-time & Connectivity

### 1.1 Mercure Hub Integration

**Goal:** Native WebSocket support via FrankenPHP for instant notifications.

```bash
# Expected command
make integrate-mercure
```

**Deliverables:**
- Mercure configuration in `config/packages/mercure.yaml`
- Example feature: `src/Notification/Features/LiveUpdates/`
- Documentation: `docs/guides/mercure-integration.md`

### 1.2 Event Sourcing Lite

**Goal:** Pattern for critical business slices (e.g., Billing).

**Deliverables:**
- ADR: `docs/adr/0010-event-sourcing-lite.md`
- Example feature: `src/Billing/Features/ProcessPayment/`
- Test patterns for event sourcing

### 1.3 SDK Generator

**Goal:** Auto-generate TypeScript clients from Features/Dto.php

**Deliverables:**
- Script: `scripts/generate-sdk.sh`
- Output: `packages/client-sdk/`
- Documentation: `docs/guides/sdk-generation.md`

---

## 🟨 Q2: AI & Agentic Autonomy

### 2.1 Self-Healing CI

**Goal:** CI pipeline that auto-fixes type errors via AI agent.

**Deliverables:**
- GitHub Action: `.github/workflows/ai-fix.yml`
- Integration with OpenAI/Anthropic API
- Documentation: `docs/guides/ai-ci.md`

### 2.2 Interactive Scaffolding

**Goal:** `make slice` becomes interactive — AI asks for parameters and drafts logic.

**Deliverables:**
- Enhanced script: `scripts/create-slice.sh`
- AI prompts for feature description
- Auto-generate first draft of Handler logic

### 2.3 Context Injection

**Goal:** Automatic project context gathering for LLMs.

**Deliverables:**
- Script: `scripts/generate-context.sh`
- Output: `context.json` for Claude/GPT-4
- Integration with AGENTS.md

---

## 🟩 Q3: Edge & Performance

### 3.1 Static Binary Builds

**Goal:** Compile project to single binary via FrankenPHP for Edge deployment.

**Deliverables:**
- Documentation: `docs/guides/static-build.md`
- Script: `scripts/build-static.sh`
- Deployment guide: Fly.io, Cloudflare

### 3.2 Memory Management

**Goal:** Worker profiling for long-running sessions.

**Deliverables:**
- ADR: `docs/adr/0011-memory-management.md`
- Profiling tools integration
- Example: `scripts/memory-profile.sh`

### 3.3 Native SQLite Support

**Goal:** Litestream optimization for cheap hosting.

**Deliverables:**
- Configuration for SQLite + Litestream
- Documentation: `docs/guides/sqlite-litestream.md`
- Example docker-compose for local development

---

## 🟧 Q4: Frontend & Ecosystem

### 4.1 HTMX / LiveWire Presets

**Goal:** Ready components for SPA-vibe while staying in PHP.

**Deliverables:**
- Example: `src/Shared/Infrastructure/Components/`
- Documentation: `docs/guides/htmx-presets.md`
- Template components

### 4.2 Public Templates Marketplace

**Goal:** Ready-made modules marketplace (Auth, Blog, Payments).

**Deliverables:**
- `modules/` directory structure
- Package registry documentation
- Template: `modules/authentication/`
- Template: `modules/payments/`

---

## 📋 Release Cadence

| Quarter | Focus | Status |
|---------|-------|--------|
| Q1 | Real-time & Connectivity | 🔜 Next |
| Q2 | AI & Agentic Autonomy | 📋 Planned |
| Q3 | Edge & Performance | 📋 Planned |
| Q4 | Frontend & Ecosystem | 📋 Planned |

---

## Contributing

Want to help? Pick a task from any quadrant:

1. Check open issues with label `good-first-issue`
2. Review ADRs in `docs/adr/`
3. Propose new modules in `modules/`

---

**"Stop refactoring. Start delivering."**
