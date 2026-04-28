# dev/

Developer-facing helpers and codegen. **Not** Symfony binaries — those live in `bin/`. Counterpart to `ops/` (operations / deployment).

| Script | Purpose |
|---|---|
| `create-slice.sh` | Scaffolds `src/{Context}/Features/{Feature}/` per ADR-0001 (Context = DDD Bounded Context). Invoked via `make slice context=Foo feature=Bar`. |
| `new-adr.sh` | Generates a new ADR file with required front-matter. Invoked via `make adr title="My Decision"`. |
| `check-docs.sh` | Lints ADR front-matter, broken `ADR-XXXX` references, and the `AGENTS.md` token budget. Invoked via `make docs-check`. |
