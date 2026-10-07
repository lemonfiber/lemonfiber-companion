<?php

declare(strict_types=1);

use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Operator\Internal\ViewModels\WhatOneFindingSays;
use Modules\Operator\Internal\ViewModels\WhichFamilyToRead;
use Tests\Support\Fakes\AStackThatWasAsked;
use Tests\Support\TheHealthScreenOfTheLoft;

// An operator sees a stack's findings worst first, and can read one family of
// them at a time.

/** A run whose worst finding is not the one the checks reached first. */
function aRunWhoseWorstRanLast(): Report
{
    return Report::of(Overall::Broken, Findings::of(
        Finding::of(
            Check::of('storage.room'),
            Category::Storage,
            'Room to grow',
            Conclusion::Passed,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
        Finding::of(
            Check::of('vpn.egress-match'),
            Category::Vpn,
            'Torrent traffic leaves through the tunnel',
            Conclusion::Failed,
            WhatTheCheckSaid::wentWrong(
                Code::of('VPN-3'),
                'Your address was visible to the swarm',
                Remedies::none(),
                Severity::Critical,
                Standing::Remediable,
                WhatItSaysUnderneath::none(),
            ),
            WhoPutItThere::bundled(),
        ),
    ));
}

/** A run with something to say about two families and nothing about the other seven. */
function aRunAcrossTwoFamilies(): Report
{
    return Report::of(Overall::Degraded, Findings::of(
        Finding::of(
            Check::of('queue.stuck'),
            Category::Queue,
            'Two downloads have not moved',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
        Finding::of(
            Check::of('queue.imports'),
            Category::Queue,
            'An import keeps failing',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
        Finding::of(
            Check::of('storage.room'),
            Category::Storage,
            'The disk is nearly full',
            Conclusion::Warned,
            WhatTheCheckSaid::nothingWrong(),
            WhoPutItThere::bundled(),
        ),
    ));
}

it('shows the worst finding first, whatever order the checks ran in', function (): void {
    // The engine sends findings in the order the checks ran, and a list drawn
    // straight off the envelope looks ordered on any report whose worst finding
    // happened to run first. This one deliberately is not that report: a
    // passing storage check arrives ahead of a critical failure, and the screen
    // is what has to put them the right way round.
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunWhoseWorstRanLast()));

    $titles = array_map(
        static fn(WhatOneFindingSays $row): string => $row->title,
        $screen->findings(),
    );

    expect($titles)->toBe([
        'Torrent traffic leaves through the tunnel',
        'Room to grow',
    ]);
});

it('offers the families this run has something to say about, and no others', function (): void {
    // Storage before Queue, which is the engine's own order rather than the
    // order the checks ran — the two queue findings arrived first. And seven
    // families are missing, because a control leading to a blank screen teaches
    // an operator that the row is not worth reading.
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $offered = array_map(
        static fn(WhichFamilyToRead $family): array => [$family->family, $family->howMany, $family->isOpen],
        $screen->families(),
    );

    expect($offered)->toBe([
        [Category::Storage->value, 1, false],
        [Category::Queue->value, 2, false],
    ]);

    // And each control has a word for itself and a line to show it in, because
    // a control drawn from a key nothing resolves renders the key.
    $storage = $screen->families()[0];

    expect($storage->said)->toBe(Category::Storage->saidOnTheScreen())
        ->and(__($storage->said))->not->toBe($storage->said)
        // Both replacements land, which is the whole of what the line does: a
        // name with no number beside it is a control an operator has to open
        // in order to find out whether it was worth opening.
        ->and(__('health.family_and_count', ['family' => 'Storage', 'count' => $storage->howMany]))
        ->toContain('Storage')
        ->toContain('1');
});

it('reading one family narrows the report to it', function (): void {
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3);

    $screen->read(Category::Queue->value);

    $about = array_map(
        static fn(WhatOneFindingSays $row): string => $row->about,
        $screen->findings(),
    );

    expect($screen->isNarrowed())->toBeTrue()
        ->and($screen->howMany())->toBe(2)
        ->and($about)->toBe([
            Category::Queue->saidOnTheScreen(),
            Category::Queue->saidOnTheScreen(),
        ]);

    // The row itself does not narrow with the list. Both families keep their
    // control and their count — a row that collapsed to the family being read
    // would take away the only way back to the other one — and the one being
    // read says so, because the template has nothing else to draw it from.
    $offered = array_map(
        static fn(WhichFamilyToRead $family): array => [$family->family, $family->howMany, $family->isOpen],
        $screen->families(),
    );

    expect($offered)->toBe([
        [Category::Storage->value, 1, false],
        [Category::Queue->value, 2, true],
    ]);
});

it('reading the family already open widens back out', function (): void {
    // The way back is the way in, which is the gesture somebody makes without
    // being told: there is no separate "all" control to go and find.
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $screen->read(Category::Queue->value);
    $screen->read(Category::Queue->value);

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3);

    // And the control that was open closes with the report it narrowed. The
    // row is the only thing on the frame that says which family is being read
    // — the list below it looks the same whether nine findings arrived or nine
    // were left after narrowing — so a control still marked open over a report
    // that has widened tells the operator they are reading the queue while
    // they are reading everything. Asserted on the way out and not only on the
    // way in, because the two are separate reads of the same state and only
    // the way in has ever been looked at.
    $closed = array_map(
        static fn(WhichFamilyToRead $family): bool => $family->isOpen,
        $screen->families(),
    );

    expect($closed)->toBe([false, false]);

    // And naming another family while one is open moves to it rather than
    // widening, which is the other half of the same tap.
    $screen->read(Category::Queue->value);
    $screen->read(Category::Storage->value);

    expect($screen->isNarrowed())->toBeTrue()
        ->and($screen->howMany())->toBe(1)
        ->and($screen->findings()[0]->about)->toBe(Category::Storage->saidOnTheScreen());
});

it('a value naming no family shows the whole report rather than nothing', function (): void {
    // The screen's own state is the only thing that writes it, so this is a
    // value that cannot arrive — and `tryFrom` is what makes that a fact rather
    // than a hope. What it prevents is a blank report where the operator
    // expected a report, which is a worse answer than a tap that does nothing.
    $screen = TheHealthScreenOfTheLoft::theHealthScreen(AStackThatWasAsked::saying(aRunAcrossTwoFamilies()));

    $screen->read('a-family-this-engine-has-never-heard-of');

    $open = array_map(
        static fn(WhichFamilyToRead $family): bool => $family->isOpen,
        $screen->families(),
    );

    expect($screen->isNarrowed())->toBeFalse()
        ->and($screen->howMany())->toBe(3)
        ->and($screen->findings())->toHaveCount(3)
        ->and($open)->toBe([false, false]);
});
