# Kernel

The language every other module speaks. Its manifest requires PHP and
`ext-mbstring` and nothing else, and every module may depend on it.

Everything is published under `Modules\Kernel\Api`:

| | |
|---|---|
| Ports | Interfaces an adapter implements and a screen asks, such as `Asking`, `Clock` and `SecureStorage` |
| Values | Immutable types built through named constructors, such as `Stack`, `Session` and `Finding` |
| Outcomes | What a port answers with where the answer can be an `Obstacle`, such as `WhatCameBack` |
| Exceptions | Mostly what a named constructor throws when it is given something that is not a value |

The kernel holds no IO, no clock of its own and no framework. An adapter module turns the
outside world into these types, and a surface module draws them.
