<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Held;
use Modules\Kernel\Api\Whereabouts;

use function sprintf;

/** Where the operator was. */
function theScreenTheyLeft(): Whereabouts
{
    return Whereabouts::onTheScreen('stack.backups.restore');
}

/**
 * Both branches, each naming itself and everything it was handed.
 *
 * Written once rather than per test, the way `foldReach` is, so that neither
 * arm can quietly stop using an argument — an arm that ignored what it was
 * given would still pass a test that only asked which side ran.
 */
function foldHeld(Held $held): Code
{
    return $held->either(
        fresh: static fn(Whereabouts $was): Code => Code::of(sprintf('fresh:%s', $was->screen())),
        restored: static fn(object $keeping, Whereabouts $was): Code => Code::of(
            sprintf('restored:%s:%s', $keeping::class, $was->screen()),
        ),
    );
}

it('N1-R38 — a first visit has nothing to restore, and says so', function (): void {
    // An empty payload and a real one have the same shape. A screen that cannot
    // tell them apart renders the empty one as though the operator had cleared
    // the form themselves.
    expect(foldHeld(Held::nothingYet(theScreenTheyLeft()))->shown())
        ->toBe('fresh:stack.backups.restore');
});

it('N1-R38 — a return hands back what was done there', function (): void {
    $keeping = Code::of('half-typed');

    expect(foldHeld(Held::keeping(theScreenTheyLeft(), $keeping))->shown())
        ->toBe(sprintf('restored:%s:stack.backups.restore', Code::class));
});

it('N1-R38 — both arms know which screen it belongs to', function (): void {
    // Restoring the wrong screen's work is worse than restoring none: it puts
    // one screen's half-finished input in front of somebody looking at another.
    $was = theScreenTheyLeft();

    expect(Held::nothingYet($was)->was()->is($was))->toBeTrue()
        ->and(Held::keeping($was, Code::of('x'))->was()->is($was))->toBeTrue();
});

it('N1-R38 — the two arms take different arguments', function (): void {
    // A `bool` here would make "nothing to restore" and "restore this" the same
    // branch, and the branch that wins is whichever the screen's author
    // happened to test. The arms differ in arity, so one cannot be read as the
    // other.
    $fresh = foldHeld(Held::nothingYet(theScreenTheyLeft()))->shown();
    $restored = foldHeld(Held::keeping(theScreenTheyLeft(), Code::of('x')))->shown();

    expect($fresh)->not->toBe($restored)
        ->and($fresh)->toStartWith('fresh:')
        ->and($restored)->toStartWith('restored:');
});
