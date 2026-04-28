#!/usr/bin/env bash
# Generates a new ADR with required YAML front-matter.
#
# Usage: make adr title="My Decision Title"
#    or: ./dev/new-adr.sh "My Decision Title"

set -euo pipefail

TITLE="${1:-}"
if [[ -z "$TITLE" ]]; then
    echo "Usage: ./dev/new-adr.sh \"Decision Title\"" >&2
    exit 1
fi

ADR_DIR="docs/adr"
mkdir -p "$ADR_DIR"

# Highest existing ADR number, default to 0.
LAST=$(ls "$ADR_DIR" 2>/dev/null | grep -E '^[0-9]{4}-' | sort | tail -n1 | sed -E 's/^([0-9]{4})-.*/\1/' | sed 's/^0*//')
LAST="${LAST:-0}"
NEXT=$(printf '%04d' "$((LAST + 1))")

SLUG=$(echo "$TITLE" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+|-+$//g')
FILE="$ADR_DIR/${NEXT}-${SLUG}.md"
TODAY=$(date +%Y-%m-%d)

if [[ -e "$FILE" ]]; then
    echo "Refusing to overwrite $FILE" >&2
    exit 1
fi

cat > "$FILE" <<EOF
---
id: ADR-${NEXT}
title: ${TITLE}
status: Proposed
date: ${TODAY}
supersedes: []
superseded_by: []
audience: both
summary: "ONE-SENTENCE summary suitable for agents skimming front-matter only."
---

# ADR-${NEXT}: ${TITLE}

**TL;DR:** Rewrite the summary above into 1–3 sentences here for human readers.

## Context

What problem are we solving? Constraints, prior art, forces in tension.

## Decision

What we are doing, in concrete terms.

## Consequences

### Positive

-

### Negative

-

## References

-
EOF

echo "Created $FILE"
