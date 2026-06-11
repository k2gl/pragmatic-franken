#!/usr/bin/env bash
# Lints documentation invariants — docs must match reality:
# - Every ADR has YAML front-matter with id/title/status/date/audience/summary.
# - No broken ADR-XXXX references in markdown files.
# - AGENTS.md fits the Tier-1 budget (≤ 8 KB, ≤ ~2000 tokens at 4 chars/token).
# - Every `make <target>` mentioned in AGENTS.md/README exists in the Makefile.
# - Routes claimed in AGENTS.md exist as #[Route] attributes in src/.
# - README ADR table is in sync with docs/adr/*.
# - Files referenced from AGENTS.md exist.

set -euo pipefail

errors=0
warn() { echo "::warn:: $*"; }
fail() { echo "::error:: $*"; errors=$((errors + 1)); }

# 1. ADR front-matter
for adr in docs/adr/*.md; do
    [[ -e "$adr" ]] || continue
    if ! head -1 "$adr" | grep -q '^---$'; then
        fail "$adr: missing YAML front-matter"
        continue
    fi
    for field in id title status date audience summary; do
        if ! awk '/^---$/{c++; next} c==1' "$adr" | grep -qE "^${field}:"; then
            fail "$adr: front-matter missing '${field}'"
        fi
    done
done

# 2. Broken ADR-XXXX references
declare -A adr_ids=()
for adr in docs/adr/*.md; do
    [[ -e "$adr" ]] || continue
    id=$(awk '/^---$/{c++; next} c==1' "$adr" | grep -E '^id:' | head -1 | sed -E 's/^id:[[:space:]]*//;s/[[:space:]]+$//')
    [[ -n "$id" ]] && adr_ids["$id"]=1
done
while IFS= read -r ref; do
    if [[ -z "${adr_ids[$ref]:-}" ]]; then
        fail "Broken reference '$ref' (no matching ADR file)"
    fi
done < <(grep -rhoE 'ADR-[0-9]{4}' --include='*.md' docs/ AGENTS.md README.md 2>/dev/null | sort -u)

# 3. AGENTS.md size budget
if [[ -f AGENTS.md ]]; then
    bytes=$(wc -c < AGENTS.md)
    chars=$(wc -m < AGENTS.md)
    approx_tokens=$((chars / 4))
    if (( bytes > 8192 )); then
        fail "AGENTS.md is ${bytes} bytes (budget ≤ 8192)"
    fi
    if (( approx_tokens > 2000 )); then
        fail "AGENTS.md ≈ ${approx_tokens} tokens (budget ≤ 2000)"
    fi
    echo "AGENTS.md: ${bytes} bytes, ~${approx_tokens} tokens"
fi

# 4. `make <target>` claims in AGENTS.md / README.md must exist in the Makefile
while IFS= read -r target; do
    case "$target" in it|sure|sense|a|an|the) continue ;; esac  # prose, not targets
    if ! grep -qE "^${target}:" Makefile; then
        fail "Documented 'make ${target}' has no Makefile target"
    fi
done < <(grep -rhoE 'make [a-z][a-z0-9-]*' AGENTS.md README.md | awk '{print $2}' | sort -u)

# 5. Health-probe routes claimed in AGENTS.md must exist in src/
for route in /healthz /ready; do
    if grep -q "${route}" AGENTS.md && ! grep -rq "Route('${route}'" src/; then
        fail "AGENTS.md claims route '${route}' but no #[Route('${route}')] exists in src/"
    fi
done

# 6. README ADR table ↔ docs/adr/* sync
for adr in docs/adr/*.md; do
    [[ -e "$adr" ]] || continue
    if ! grep -q "$(basename "$adr")" README.md; then
        fail "README.md ADR table is missing $(basename "$adr")"
    fi
done
while IFS= read -r ref; do
    if [[ ! -e "$ref" ]]; then
        fail "README.md links missing ADR file '$ref'"
    fi
done < <(grep -oE 'docs/adr/[0-9]{4}[a-z0-9-]*\.md' README.md | sort -u)

# 7. Files referenced from AGENTS.md must exist
while IFS= read -r f; do
    if [[ ! -e "$f" ]]; then
        fail "AGENTS.md references missing file '$f'"
    fi
done < <(grep -oE 'docs/(adr|guides|recipes)/[A-Za-z0-9._-]+\.md' AGENTS.md | sort -u)

if (( errors > 0 )); then
    echo "${errors} doc-check error(s)" >&2
    exit 1
fi
echo "docs-check OK"
