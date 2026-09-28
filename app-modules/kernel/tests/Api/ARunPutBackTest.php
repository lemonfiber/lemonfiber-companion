<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AChangeAndWhy;
use Modules\Kernel\Api\AChangePutBack;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\HowPuttingARunBackIsGoing;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\UndoSaysNothing;
use Modules\Kernel\Api\WhatGoingBackDoes;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhatPuttingTheRunBackCameTo
{
    public function __construct(public string $said) {}
}

/** A report, leaving whatever a case says. */
function aRunPutBackLeaving(ChangesAndWhy $left, WhetherItWasRehearsed $rehearsed = WhetherItWasRehearsed::CarriedOut): ARunPutBack
{
    return ARunPutBack::reported(
        $rehearsed,
        WhatWentBack::these(AChangePutBack::against('lemonfiber', WhatGoingBackDoes::Restore)),
        $left,
        ChangesAndWhy::these(),
    );
}

/** Which arm a following took, as a line. */
function whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing $going): string
{
    return $going->either(
        stillRunning: static fn(): WhatPuttingTheRunBackCameTo => new WhatPuttingTheRunBackCameTo('running'),
        done: static fn(ARunPutBack $report): WhatPuttingTheRunBackCameTo => new WhatPuttingTheRunBackCameTo(sprintf('done, %s', $report->rehearsed()->value)),
        refused: static fn(ARefusalInItsWords $why): WhatPuttingTheRunBackCameTo => new WhatPuttingTheRunBackCameTo(sprintf('refused, %s', $why->summary())),
        ended: static fn(): WhatPuttingTheRunBackCameTo => new WhatPuttingTheRunBackCameTo('ended'),
        met: static fn(Obstacle $why): WhatPuttingTheRunBackCameTo => new WhatPuttingTheRunBackCameTo($why->name),
    )->said;
}

it('keeps what a reversal was against and what it did, less the space around the target', function (): void {
    $change = AChangePutBack::against(' sonarr ', WhatGoingBackDoes::Reconfigure);

    expect([$change->target(), $change->does()])->toBe(['sonarr', WhatGoingBackDoes::Reconfigure]);
});

it('refuses a reversal against nothing, naming the field', function (): void {
    expect(static fn(): AChangePutBack => AChangePutBack::against(' ', WhatGoingBackDoes::Delete))
        ->toThrow(UndoSaysNothing::class, '`target` blank');
});

it('keeps a change and what is said of it, less the space around each', function (): void {
    $change = AChangeAndWhy::said(' sonarr ', ' the service did not answer ');

    expect([$change->target(), $change->because()])->toBe(['sonarr', 'the service did not answer']);
});

it('refuses a change with either half blank, naming which', function (string $target, string $because, string $field): void {
    expect(static fn(): AChangeAndWhy => AChangeAndWhy::said($target, $because))
        ->toThrow(UndoSaysNothing::class, sprintf('`%s` blank', $field));
})->with([
    'no target' => [' ', 'it did not answer', 'target'],
    'no reason' => ['sonarr', ' ', 'because'],
]);

it('keeps each list in the stack\'s order, whatever it was handed under', function (): void {
    $restored = AChangePutBack::against('lemonfiber', WhatGoingBackDoes::Restore);
    $deleted = AChangePutBack::against('sonarr', WhatGoingBackDoes::Delete);
    $left = AChangeAndWhy::said('sonarr', 'it did not answer');
    $noted = AChangeAndWhy::said('lemonfiber', 'the library stays where it was moved');
    $reversed = WhatWentBack::these(...['b' => $restored, 'a' => $deleted]);
    $said = ChangesAndWhy::these(...['x' => $left, 'y' => $noted]);

    expect(iterator_to_array($reversed, preserve_keys: true))->toBe([$restored, $deleted])
        ->and($reversed)->toHaveCount(2)
        ->and(iterator_to_array($said, preserve_keys: true))->toBe([$left, $noted])
        ->and($said)->toHaveCount(2);
});

it('keeps every part of a report as the stack gave it', function (): void {
    $reversed = WhatWentBack::these(AChangePutBack::against('lemonfiber', WhatGoingBackDoes::Restore));
    $left = ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'it did not answer'));
    $noted = ChangesAndWhy::these(AChangeAndWhy::said('lemonfiber', 'the library stays where it was moved'));
    $report = ARunPutBack::reported(WhetherItWasRehearsed::Rehearsed, $reversed, $left, $noted);

    expect([$report->rehearsed(), $report->reversed(), $report->left(), $report->noted()])
        ->toBe([WhetherItWasRehearsed::Rehearsed, $reversed, $left, $noted]);
});

it('is complete only where nothing was left', function (): void {
    expect(aRunPutBackLeaving(ChangesAndWhy::these())->leftNothing())->toBeTrue()
        ->and(aRunPutBackLeaving(ChangesAndWhy::these(AChangeAndWhy::said('sonarr', 'it did not answer')))->leftNothing())->toBeFalse();
});

it('follows a run going back to each of its five arms', function (): void {
    $refused = ARefusalInItsWords::said('Nothing was changed at 1790150000', '', WhatTheRefusalNamed::nothing());

    expect(whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing::stillRunning()))->toBe('running')
        ->and(whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing::done(aRunPutBackLeaving(ChangesAndWhy::these(), WhetherItWasRehearsed::Rehearsed))))->toBe('done, rehearsed')
        ->and(whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing::refused($refused)))->toBe('refused, Nothing was changed at 1790150000')
        ->and(whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing::ended()))->toBe('ended')
        ->and(whichArmPuttingTheRunBackTook(HowPuttingARunBackIsGoing::met(Obstacle::StackDidNotAnswer)))->toBe(Obstacle::StackDidNotAnswer->name);
});

it('says each kind of reversal by its own line', function (): void {
    foreach (WhatGoingBackDoes::cases() as $does) {
        expect($does->saidOnTheScreen())->toBe(sprintf('stacks.run_back.does.%s', $does->value));
    }
});
