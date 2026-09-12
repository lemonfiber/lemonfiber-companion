<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function get_class_methods;
use function it;

use Modules\Kernel\Api\Attempted;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Standing;
use ReflectionClass;
use ReflectionMethod;

use function sprintf;

const THE_STACK_ASKED = 'a1b2c3d4e5f60718';

/**
 * The stack an attempt was made against.
 *
 * Named apart from `StackTest`'s `theStackAskedOf()`, which returns a whole `Stack`: the
 * module suites share one namespace, so two helpers of the same name are a fatal
 * the moment both load — and here the two would have had different return types,
 * which is the version that fails confusingly rather than immediately (`G10`).
 */
function theStackAskedOf(): StackId
{
    return StackId::of(Nonce::of(THE_STACK_ASKED));
}

function itNeverArrived(): Problem
{
    return Problem::of(
        Code::of('COMPANION-UNREACHABLE'),
        Severity::Error,
        Standing::Actionable,
        'This stack cannot be reached from here.',
        'Nothing was changed.',
        Remedies::of(),
    );
}

/** Which arm answered, as a word. */
function howItWent(Attempted $attempt): string
{
    return $attempt->either(
        delivered: fn(StackId $on): Code => Code::of(sprintf('delivered to %s', $on->stored())),
        refused: fn(Problem $why, StackId $on): Code => Code::of(sprintf('%s on %s', $why->code()->shown(), $on->stored())),
    )->shown();
}

it('says the stack received it', function (): void {
    expect(howItWent(Attempted::delivered(theStackAskedOf())))->toBe(sprintf('delivered to %s', THE_STACK_ASKED));
});

it('N1-R40 — refuses an action it could not deliver', function (): void {
    // Refused rather than retained. ADR-0020 spends its length rejecting the
    // obvious kindness of holding it until the stack comes back.
    expect(howItWent(Attempted::refused(theStackAskedOf(), itNeverArrived())))->toBe(sprintf('COMPANION-UNREACHABLE on %s', THE_STACK_ASKED));
});

it('N1-R40 — the refusal names the stack', function (): void {
    // An operator who pressed a button and saw a red message needs to know
    // which machine it was about. Carried on the type rather than left to the
    // screen, so it cannot depend on which screen the refusal reached.
    $named = null;

    Attempted::refused(theStackAskedOf(), itNeverArrived())->either(
        delivered: fn(StackId $on): Code => Code::of($on->stored()),
        refused: function (Problem $why, StackId $on) use (&$named): Code {
            $named = $on->stored();

            return $why->code();
        },
    );

    expect($named)->toBe(THE_STACK_ASKED);
});

it('N1-R40 — names the stack on the delivered arm too', function (): void {
    // A screen showing several stacks needs it either way.
    expect(Attempted::delivered(theStackAskedOf())->on()->stored())->toBe(THE_STACK_ASKED)
        ->and(Attempted::refused(theStackAskedOf(), itNeverArrived())->on()->stored())->toBe(THE_STACK_ASKED);
});

it('N1-R41 — has no third state meaning pending', function (): void {
    // The absence *is* the requirement. A type with no arm for "pending"
    // cannot present an action as pending, whatever a screen would like to do.
    $makers = [];

    foreach (new ReflectionClass(Attempted::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic()) {
            $makers[] = $method->getName();
        }
    }

    expect($makers)->toBe(['delivered', 'refused']);
});

it('N1-R43 — a refused attempt says nothing about the capability', function (): void {
    // The attempt failed; the capability did not become unavailable. This type
    // carries no capability at all, which is how it stays true — there is
    // nothing here for a screen to read as "that button is gone now".
    expect(get_class_methods(Attempted::class))->not->toContain('capability')
        ->and(get_class_methods(Attempted::class))->toBe(['delivered', 'refused', 'on', 'either']);
});
