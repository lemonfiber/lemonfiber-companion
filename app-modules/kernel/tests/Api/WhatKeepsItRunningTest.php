<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatKeepsItRunning;

use function sprintf;

it('N16-R5 — a machine with no manager this product configures says so', function (): void {
    // The one question a screen asks before drawing anything about coming back
    // after a restart. False here is not *off*: what follows it is the
    // contract's `instruction` — what to do instead — rather than a control
    // nobody can use.
    expect(WhatKeepsItRunning::Unsupported->configuresAnything())->toBeFalse();
});

it('N16-R5 — the two managers this product sets up are both configured', function (): void {
    // Named rather than derived from *not unsupported*, which is the assertion
    // the method already makes and would prove nothing twice. A third manager
    // added without an opinion about it fails here rather than passing as
    // whatever the negation happened to give.
    $configures = [];

    foreach (WhatKeepsItRunning::cases() as $manager) {
        if ($manager->configuresAnything()) {
            $configures[] = $manager->value;
        }
    }

    expect($configures)->toBe(['launchd', 'systemd']);
});

it('L7 — every manager names a line, built from the case', function (): void {
    foreach (WhatKeepsItRunning::cases() as $manager) {
        expect($manager->saidOnTheScreen())
            ->toBe(sprintf('stacks.keeps-running.%s', $manager->value), $manager->name);
    }
});
