#!/usr/bin/env bash
# Agent smoke: prove that SCAFFOLDED code passes the same gates as human code,
# with zero manual edits. This is the machine-verifiable backing for the
# README claim "agents land green here":
#
#   1. dev/create-slice.sh generates a slice (what an agent runs via `make slice`)
#   2. Pint + PHPStan level 10 + the slice's test must pass untouched
#   3. The scaffold is removed; the tree is left exactly as found
#
# Required CI job (ci.yml). Run locally: make agent-smoke (inside the container:
# ./dev/agent-smoke.sh). Scaffold drift that breaks the gates = template bug.
set -euo pipefail

CONTEXT="AgentSmoke"
FEATURE="Probe"
SRC_DIR="src/Context/$CONTEXT"
TEST_DIR="tests/Context/$CONTEXT"

if [ -e "$SRC_DIR" ] || [ -e "$TEST_DIR" ]; then
    echo "agent-smoke: $SRC_DIR or $TEST_DIR already exists — refusing" >&2
    exit 1
fi

cleanup() { rm -rf "$SRC_DIR" "$TEST_DIR"; }
trap cleanup EXIT

echo "[agent-smoke] scaffolding $CONTEXT/$FEATURE..."
./dev/create-slice.sh "$CONTEXT" "$FEATURE"

echo "[agent-smoke] pint..."
vendor/bin/pint --test "$SRC_DIR" "$TEST_DIR"

echo "[agent-smoke] phpstan (level 10)..."
vendor/bin/phpstan analyze "$SRC_DIR" "$TEST_DIR" --level=10 --no-progress --memory-limit=1G

echo "[agent-smoke] phpunit (scaffolded test)..."
vendor/bin/phpunit "$TEST_DIR"

echo "[agent-smoke] ✅ scaffolded code passes Pint + PHPStan 10 + tests untouched."
