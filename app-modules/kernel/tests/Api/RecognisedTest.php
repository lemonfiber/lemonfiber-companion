<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Recognised;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Standing;
use ReflectionMethod;

const WHAT_IT_PRESENTED = '3b8c1f09a7d24e6b5c0f81a2d93e47b6c8150af2937d6e4b1c05a8f39d27e64b';
const SOMETHING_ELSE = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

function theLoft(string $presenting = WHAT_IT_PRESENTED): Stack
{
    return Stack::of(
        StackId::of(Nonce::of('a1b2c3d4e5f60718')),
        'the loft',
        Address::of('https://stack.local'),
        Fingerprint::of($presenting),
    );
}

/** Which arm answered, as a word. */
function whatItSaw(Recognised $seen): string
{
    return $seen->either(
        paired: fn(): Code => Code::of('paired'),
        stranger: fn(Obstacle $why): Code => Code::of($why->value),
    )->shown();
}

it('N1-R19 — recognises the machine it was introduced to', function (): void {
    expect(whatItSaw(theLoft()->recognises(Fingerprint::of(WHAT_IT_PRESENTED))))->toBe('paired');
});

it('N1-R20 — refuses a machine presenting something else', function (): void {
    // Refused rather than warned about. There is no arm here meaning "not the
    // paired stack, but carry on" — a warning is a dialog with a way past it,
    // and the way past it is the thing an attacker needs.
    expect(whatItSaw(theLoft()->recognises(Fingerprint::of(SOMETHING_ELSE))))
        ->toBe(Obstacle::StackIsNotTheOnePaired->value);
});

it('N1-R20 — reports it as this not being the machine, and offers re-pairing', function (): void {
    // The requirement asks for three things about the report, and all three are
    // already attached to the obstacle: it is critical rather than advisory, it
    // has a code somebody can search for, and it is actionable — which is what
    // puts "pair again" on the screen rather than instructions to read.
    expect(Obstacle::StackIsNotTheOnePaired->severity())->toBe(Severity::Critical)
        ->and(Obstacle::StackIsNotTheOnePaired->code()->shown())->toBe('COMPANION-CERTIFICATE-CHANGED')
        ->and(Obstacle::StackIsNotTheOnePaired->standing())->toBe(Standing::Actionable);
});

it('N1-R20 — the stranger arm cannot be handed a gentler obstacle', function (): void {
    // `asAStranger()` takes no argument on purpose. Letting a caller choose the
    // obstacle would let a caller choose one that is not critical, which is the
    // warning this requirement refuses, arrived at from a different direction.
    expect(new ReflectionMethod(Recognised::class, 'asAStranger')->getParameters())->toBe([]);
});

it('N1-R22 — is pinned to the stack, so another route changes nothing', function (): void {
    // The same machine reached at a different address is still that machine.
    // A check hanging off the address would re-open the question of identity
    // every time the route changed, which is what this requirement forbids.
    $atHome = theLoft();
    $away = Stack::of(
        $atHome->id(),
        $atHome->name(),
        Address::of('https://10.0.0.4:8443'),
        Fingerprint::of(WHAT_IT_PRESENTED),
    );

    expect(whatItSaw($away->recognises(Fingerprint::of(WHAT_IT_PRESENTED))))->toBe('paired');
});
