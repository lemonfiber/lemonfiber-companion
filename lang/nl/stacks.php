<?php

declare(strict_types=1);

// One group, kept in a file per area under `stacks/`. Laravel reads this file,
// and every key keeps its `stacks.` name.
return [
    ...require __DIR__ . '/stacks/keeping-things-running.php',
    ...require __DIR__ . '/stacks/what-the-machine-changed.php',
    ...require __DIR__ . '/stacks/what-the-machine-holds.php',
    ...require __DIR__ . '/stacks/the-household.php',
    ...require __DIR__ . '/stacks/moving-in.php',
    ...require __DIR__ . '/stacks/room-and-copies.php',
    ...require __DIR__ . '/stacks/the-line-and-help.php',
    ...require __DIR__ . '/stacks/putting-the-configuration-back.php',
];
