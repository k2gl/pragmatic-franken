## What & why

<!-- One or two sentences: what does this PR change and what problem does it solve? -->

## Architecture checklist (ADR-0001, ADR-0003)

- [ ] Feature code lives in `src/{Context}/Features/{Feature}/` — no global `Controllers/`, `Services/`, `Repositories/` dirs
- [ ] Writes/reads go through Messenger (`*Command`/`*Query` + `#[AsMessageHandler]`), or the CRUD escape hatch of ADR-0003 applies
- [ ] Handlers return Result DTOs, never Doctrine entities
- [ ] No mutable static state (worker mode, ADR-0004)
- [ ] Tests added/updated; `make ci` is green locally
- [ ] Docs updated if behavior or commands changed (`make docs-check`)

## Links

<!-- Issues, ADRs, discussions. -->
