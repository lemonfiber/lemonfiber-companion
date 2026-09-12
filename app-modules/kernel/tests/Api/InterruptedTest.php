<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Interrupted;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whereabouts;
use ReflectionClass;
use ReflectionParameter;

use function sprintf;

/** The stack whose session stopped being usable. */
function theStackTheOperatorWasOn(): StackId
{
    return StackId::of(Nonce::of('c2f1d0a99b7e4c3d85f6a0b1c2d3e4f5'));
}

/** Where the operator was when it happened. */
function whereTheOperatorWas(): Whereabouts
{
    return Whereabouts::onTheScreen('stack.readings');
}

/**
 * Both branches, each naming itself and everything it was handed.
 *
 * Written once rather than per test, the way `foldReach` is, so that every case
 * reads the same fold and neither arm can quietly stop using an argument — an
 * arm that ignored what it was given would still pass a test that only asked
 * which side ran.
 */
function foldInterrupted(Interrupted $what): Code
{
    return $what->either(
        ended: static fn(Whereabouts $was): Code => Code::of(sprintf('ended:%s', $was->screen())),
        refused: static fn(Obstacle $because, Whereabouts $was): Code => Code::of(
            sprintf('refused:%s:%s', $because->value, $was->screen()),
        ),
    );
}

it('N1-R46 — an ended session and a refused credential are read differently', function (): void {
    // The requirement in one assertion. A `bool` puts both of these through the
    // same branch, and the branch that says "that credential was not accepted"
    // to somebody whose session merely expired teaches them to distrust a stack
    // that is behaving correctly.
    $ended = Interrupted::theSessionEnded(theStackTheOperatorWasOn(), whereTheOperatorWas());
    $refused = Interrupted::theCredentialWasRefused(
        theStackTheOperatorWasOn(),
        whereTheOperatorWas(),
        Obstacle::CredentialWasRefused,
    );

    expect(foldInterrupted($ended)->shown())->toBe('ended:stack.readings')
        ->and(foldInterrupted($refused)->shown())->toBe('refused:credential_refused:stack.readings');
});

it('N1-R46 — the ended arm is handed no obstacle to show', function (): void {
    // Not an oversight — the mechanism. `Obstacle` holds the three things
    // N1-R10 names as blocking a connection, and a session expiring is not one
    // of them: it is routine, and it is exactly what N1-R46 says must read
    // differently from a refusal. A fourth obstacle case would invite the
    // conflation the requirement forbids.
    //
    // So the arms take different arguments, which is what makes one unreadable
    // as the other. Checked on the signature rather than argued for in a
    // comment: an `ended` arm that could be handed an obstacle is an `ended`
    // arm somebody will pass the `refused` closure to.
    // Reached by walking the methods rather than by naming one. `getMethod()`
    // raises a checked `ReflectionException`, and the house rule is that a
    // checked exception cannot be raised inside a closure — which every Pest
    // body is.
    $arms = [];

    foreach (new ReflectionClass(Interrupted::class)->getMethods() as $method) {
        if ($method->getName() === 'either') {
            $arms = array_map(
                static fn(ReflectionParameter $arm): string => $arm->getName(),
                $method->getParameters(),
            );
        }
    }

    expect($arms)->toBe(['ended', 'refused']);

    // And the ended arm really receives one argument, not an obstacle it has
    // been given the option of ignoring.
    expect(foldInterrupted(Interrupted::theSessionEnded(theStackTheOperatorWasOn(), whereTheOperatorWas()))->shown())
        ->toBe('ended:stack.readings');
});

it('N1-R44 — both arms come back to the screen the operator was on', function (): void {
    // The clause a screen loses by accident. The interruption arrives, the app
    // has a perfectly good error to show, and where the operator *was* is a
    // local in a frame that has already gone. Nothing fails; they sign in and
    // arrive at the start, with whatever they were halfway through gone.
    $where = Whereabouts::onTheScreen('stack.backups.restore');
    $on = theStackTheOperatorWasOn();

    expect(Interrupted::theSessionEnded($on, $where)->resumeAt()->is($where))->toBeTrue()
        ->and(Interrupted::theCredentialWasRefused($on, $where, Obstacle::CredentialWasRefused)->resumeAt()->is($where))
        ->toBeTrue();
});

it('N1-R44 — the screen reaches both arms of the fold as well', function (): void {
    // `resumeAt()` sits outside `either()` so that a caller choosing which
    // sentence to show cannot also decide whether returning somebody to their
    // work is part of this case. It is still handed to both arms, because the
    // sentence and the place are shown together.
    $where = Whereabouts::onTheScreen('stack.settings');
    $on = theStackTheOperatorWasOn();

    expect(foldInterrupted(Interrupted::theSessionEnded($on, $where))->shown())
        ->toBe('ended:stack.settings')
        ->and(foldInterrupted(Interrupted::theCredentialWasRefused($on, $where, Obstacle::CredentialWasRefused))->shown())
        ->toBe('refused:credential_refused:stack.settings');
});

it('N1-R11 — names its stack on both arms', function (): void {
    // A stack's session is its own. An interruption that could not name its
    // stack would sign the operator out of every stack they have.
    $on = theStackTheOperatorWasOn();
    $another = StackId::of(Nonce::of('0f1e2d3c4b5a69788796a5b4c3d2e1f0'));

    expect(Interrupted::theSessionEnded($on, whereTheOperatorWas())->on()->is($on))->toBeTrue()
        ->and(Interrupted::theSessionEnded($on, whereTheOperatorWas())->on()->is($another))->toBeFalse()
        ->and(Interrupted::theCredentialWasRefused($on, whereTheOperatorWas(), Obstacle::CredentialWasRefused)->on()->is($on))
        ->toBeTrue();
});

it('N1-R45 — names its stack and nothing that could re-pin a certificate', function (): void {
    // A credential expiring is not the machine changing — ADR-0018 is explicit
    // — so re-pairing when a session ends would throw away a pinned certificate
    // that is still correct, and then ask the operator to accept a new one.
    // That is a habit an attacker would like them to have.
    //
    // The signatures are checked in `TypesThatMustNotMeetTest`, with the other
    // requirements that are satisfied by a path not existing, because a
    // reflection loop written per subject is how one of them ends up reading
    // only the shapes its author happened to think of. What is checked here is
    // the positive half: naming the stack is enough to come back to the same
    // pinned certificate, because N1-R22 pins trust to the stack rather than to
    // where it answers.
    $on = theStackTheOperatorWasOn();

    expect(Interrupted::theSessionEnded($on, whereTheOperatorWas())->on()->stored())
        ->toBe($on->stored());
});
