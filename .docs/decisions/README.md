# Decisions about this codebase

Why this repository is shaped the way it is. Decisions about the *product* —
what the app does and refuses to do — live in the
[spec](https://github.com/lemonfiber/spec) as ADRs and requirements. These are
about the code: the tooling, the layout, and the rules.

The distinction matters when you want to change something. A rule here can be
changed by this repository alone. A requirement in the spec cannot.

| # | Decision | Status |
|---|----------|--------|
| [0001](0001-modules-as-composer-packages.md) | Modules are composer packages, so the resolver enforces the boundaries | Accepted |
| [0002](0002-command-query-separation-without-a-bus.md) | Command/query separation, without a command bus | Accepted |
| [0003](0003-no-baseline-ever.md) | There is no baseline, and there will not be one | Accepted |
| [0004](0004-blade-checks-belong-in-the-test-suite.md) | The Blade checks are tests, not a script | Accepted |
| [0005](0005-how-a-rule-changes.md) | How a rule is changed, and how one is retired | Accepted |
| [0006](0006-what-a-dependency-update-may-do-unattended.md) | What a dependency update may do unattended | Accepted |

## Writing one

Short. What forced the decision, what was chosen, what it cost, and what would
make it wrong. If a decision had no cost it probably was not a decision.
