---
audience: both
tier: 2
last_reviewed: 2026-06-11
summary: "dev/worktree.sh — isolated git-worktree dev stacks (own branch, docker project and port slot) so several AI agents or developers work in parallel without colliding."
---

# Parallel sessions (git worktree forks)

`dev/worktree.sh` creates isolated dev stacks so several sessions — human or
AI agents — run this project in parallel without colliding on the git tree,
host ports or the docker project (containers, volumes, database).

Each fork = its own git worktree (own branch, dir under `/www/wt/<prefix>-<slug>`)
+ its own docker stack: a distinct `COMPOSE_PROJECT_NAME` and a port slot
offset from the bases in `.env.dist` (the single source of the port scheme —
HTTP/HTTPS step 10 per slot, db/redis/xdebug step 1).

```bash
dev/worktree.sh new payment-flow      # create fork, bootstrap, start, migrate, seed
dev/worktree.sh ls                    # all forks: state, ports, dirty/ahead
dev/worktree.sh status payment-flow   # health (/ready) + ports
dev/worktree.sh down payment-flow     # stop (keeps the DB volume)
dev/worktree.sh up payment-flow       # start again
dev/worktree.sh rm payment-flow       # tear down stack + remove worktree
dev/worktree.sh prune --days 14       # GC merged+clean+old forks
```

Safety rules built in: the script only ever touches docker projects named
`<prefix>-<slug>`, never the primary stack; `rm`/`prune` refuse to drop a fork
with uncommitted or unmerged work unless `--force`; a stopped fork's port slot
is never reused while its volumes can still come back up.

Forks reuse the primary's already-built dev image (tagged per project) instead
of recompiling PHP per fork. Start your agent session inside the fork's dir —
it picks up `AGENTS.md` like any checkout.
