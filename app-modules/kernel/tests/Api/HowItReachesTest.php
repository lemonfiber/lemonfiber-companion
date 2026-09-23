<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWiringExplainsNothing;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;

/**
 * Everything an arm handed out, carried out of `whichever()` in one piece.
 *
 * One carrier rather than a fold per assertion, so every parameter the union
 * hands a reader is read by something — an arm whose payload no test touches is
 * an arm nothing is holding to its signature.
 */
final readonly class WhatTheReachSaid
{
    public function __construct(
        public string $arm,
        public string $subject,
        public Services $services,
        public ?WhatSettledIt $settled,
        public string $why,
    ) {}
}

function whatTheReachGave(HowItReaches $reaches): WhatTheReachSaid
{
    return $reaches->whichever(
        asked: static fn(Capability $capability, Services $services, WhatSettledIt $settled): WhatTheReachSaid
            => new WhatTheReachSaid('asked', $capability->named(), $services, $settled, ''),
        byName: static fn(ServiceId $service, string $why): WhatTheReachSaid
            => new WhatTheReachSaid('by-name', $service->named(), Services::none(), null, $why),
    );
}

/** @return list<string> */
function theServicesReached(Services $services): array
{
    $named = [];

    foreach ($services as $service) {
        $named[] = $service->named();
    }

    return $named;
}

it('takes a reader to the arm the core sent', function (): void {
    $asked = whatTheReachGave(HowItReaches::asked(
        Capability::called('download-client'),
        Services::these(ServiceId::called('sabnzbd')),
        WhatSettledIt::outright(),
    ));

    $named = whatTheReachGave(HowItReaches::byName(ServiceId::called('qbittorrent'), 'the operator said so'));

    expect($asked->arm)->toBe('asked')
        ->and($asked->subject)->toBe('download-client')
        ->and($named->arm)->toBe('by-name')
        ->and($named->subject)->toBe('qbittorrent');
});

it('hands an asked reach what answers it and how that was settled', function (): void {
    $said = whatTheReachGave(HowItReaches::asked(
        Capability::called('download-client'),
        Services::these(ServiceId::called('sabnzbd'), ServiceId::called('qbittorrent')),
        WhatSettledIt::chosen(Services::none(), WhoSettledIt::Operator, WhyItWasChosen::unstated()),
    ));

    expect(theServicesReached($said->services))->toBe(['sabnzbd', 'qbittorrent'])
        ->and($said->settled)->not->toBeNull();
});

it('carries the reason a service was named, trimmed', function (): void {
    expect(whatTheReachGave(HowItReaches::byName(ServiceId::called('qbittorrent'), '  the operator said so  '))->why)
        ->toBe('the operator said so');
});

it('refuses a by-name wiring that explains nothing', function (): void {
    // Wiring by name is not something the stack worked out — it is an
    // instruction somebody gave, and the reason is the only part a later reader
    // can evaluate. Without it the instruction cannot be told from a choice the
    // stack made, which is the one thing this arm exists to say it was not.
    expect(static fn(): HowItReaches => HowItReaches::byName(ServiceId::called('qbittorrent'), "  \n "))
        ->toThrow(AWiringExplainsNothing::class);
});

it('gives an asked reach no reason, because nobody gave it an instruction', function (): void {
    // The reason's absence is kept by the signature, not by an assertion. The
    // `asked` closure takes a capability, what answers it and how that settled,
    // and there is no parameter a reason could arrive in — so `whatTheReachGave`
    // has to supply one itself, and `expect($asked->why)` used to read that
    // literal back. It could not fail, and a mutant proved it: the arm's own
    // placeholder could be changed to anything and every test still passed.
    //
    // What this reads is what the arm does hand over.
    $asked = whatTheReachGave(HowItReaches::asked(
        Capability::called('indexer'),
        Services::none(),
        WhatSettledIt::unfilled(),
    ));

    expect($asked->arm)->toBe('asked')
        ->and($asked->subject)->toBe('indexer')
        ->and(theServicesReached($asked->services))->toBe([]);
});
