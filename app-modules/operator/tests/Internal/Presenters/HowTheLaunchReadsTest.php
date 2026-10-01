<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhyTheStacksAreHeldBack;
use Modules\Operator\Internal\Presenters\HowTheLaunchReads;

use function sprintf;
use function str_repeat;

use Tests\Support\AnObstacleOfEachKind;

/**
 * The four a launch can be, kept apart on the way to a template.
 *
 * `Launch::either()` requires all four arms, which is the requirement
 * expressed as a signature. This is where those four become fields, and the
 * failure it guards against is the collapse happening here instead.
 */
it('says no machine is paired, which is not an obstacle', function (): void {
    // A first run rather than a failure to reach. Carrying an obstacle key here
    // would have the app describe its own first launch as broken.
    $unpaired = new HowTheLaunchReads()->unpaired();

    expect($unpaired->isPaired)->toBeFalse()
        ->and($unpaired->isHeldBack)->toBeFalse()
        ->and($unpaired->met)->toBe('')
        ->and($unpaired->remedy)->toBe('')
        ->and($unpaired->opensOn)->toBe('');
});

it('says what stood in the way, by a key built from the obstacle', function (): void {
    // Every case, because the key is derived rather than listed — so a seventh
    // obstacle needs no edit here, and must not arrive without a catalogue line.
    foreach (AnObstacleOfEachKind::all() as $met) {
        $why = $met->kind();
        $blocked = new HowTheLaunchReads()->blockedBy($met);

        // That the key resolves is `EveryDerivedKeyResolvesTest`'s job — it has
        // the application container and this does not. Here the claim is only
        // that the key is built from the case rather than listed against it.
        expect($blocked->met)->toBe(sprintf('connection.%s', $why->value), $why->value)
            // An obstacle owes both, and they are not the same sentence: what
            // happened is a fact about the world, what to do about it is advice.
            ->and($blocked->remedy)->toBe(sprintf('connection.%s_action', $why->value), $why->value)
            ->and($blocked->isPaired)->toBeTrue($why->value)
            ->and($blocked->isHeldBack)->toBeFalse($why->value)
            ->and($blocked->opensOn)->toBe('', $why->value);
    }
});

it('names which machine a ready launch is about', function (): void {
    $stack = StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
    $ready = new HowTheLaunchReads()->readyFor($stack);

    expect($ready->opensOn)->toBe($stack->stored())
        ->and($ready->isPaired)->toBeTrue()
        ->and($ready->isHeldBack)->toBeFalse()
        ->and($ready->met)->toBe('')
        ->and($ready->remedy)->toBe('');
});

it('tells blocked from unpaired, which both have no stack to open on', function (): void {
    // The pair most easily collapsed: neither opens on a machine, and a screen
    // reading only `opensOn` would draw the same frame for a first run and for
    // a stack that could not be reached.
    expect(new HowTheLaunchReads()->unpaired()->isPaired)
        ->not->toBe(new HowTheLaunchReads()->blockedBy(Obstacle::of(KindOfObstacle::StackDidNotAnswer))->isPaired);
});

it('says why the stacks are held back, with what to do, and opens on none of them', function (WhyTheStacksAreHeldBack $why): void {
    $heldBack = new HowTheLaunchReads()->heldBack($why);

    expect($heldBack->isHeldBack)->toBeTrue()
        ->and($heldBack->isPaired)->toBeTrue()
        ->and($heldBack->met)->toBe($why->said())
        ->and($heldBack->remedy)->toBe($why->remedy())
        ->and($heldBack->opensOn)->toBe('');
})->with(WhyTheStacksAreHeldBack::cases());
