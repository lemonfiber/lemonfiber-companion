<?php

declare(strict_types=1);

use Modules\Kernel\Api\AboutWhat;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\ServiceIsUnnamed;
use Tests\Support\TheWordCarriedOut;

/** Which arm this took, and what it was handed. */
function whatItWasAbout(AboutWhat $about): string
{
    return $about->either(
        theMachine: static fn(): TheWordCarriedOut => new TheWordCarriedOut('the machine'),
        theService: static fn(ServiceId $service): TheWordCarriedOut
            => new TheWordCarriedOut($service->named()),
    )->said;
}

it('carries the service a finding is about, as the stack spells it', function (): void {
    expect(whatItWasAbout(AboutWhat::theService('gluetun')))->toBe('gluetun');
});

it('a finding about the machine is about no service, rather than about an empty one', function (): void {
    // `C2`'s reason for two arms instead of a nullable string: the machine is a
    // legitimate subject, and a screen reading a blank name would show a
    // finding about nothing at all.
    expect(whatItWasAbout(AboutWhat::theMachine()))->toBe('the machine');
});

it('refuses a finding that says it is about a service and names none', function (): void {
    // The engine let a field go, which is its own fault and its own sentence —
    // a caller asking to read the logs of nothing is the other one.
    expect(fn(): AboutWhat => AboutWhat::theService(''))
        ->toThrow(ServiceIsUnnamed::class, 'names none');
});

it('refuses a name that is whitespace, which is a name nobody can read', function (): void {
    // The trim is the whole of the difference between this case and the one
    // above, and a guard comparing the untrimmed string would let every one of
    // these through — a finding carrying a service called `\n` renders as a
    // finding about a service whose name is blank on the glass.
    foreach ([' ', '   ', "\t", "\n", " \t\n "] as $blank) {
        expect(fn(): AboutWhat => AboutWhat::theService($blank))
            ->toThrow(ServiceIsUnnamed::class, 'names none');
    }
});

it('keeps the name, less the whitespace around it', function (): void {
    expect(whatItWasAbout(AboutWhat::theService("  sonarr\n")))->toBe('sonarr');
});

/** What the stack calls it, or the word for none, since the arm hands back an object. */
function whatItWasCalled(AboutWhat $about): string
{
    return $about->whatItIsCalled(
        called: static fn(string $name): TheWordCarriedOut => new TheWordCarriedOut($name),
        unsaid: static fn(): TheWordCarriedOut => new TheWordCarriedOut('unsaid'),
    )->said;
}

it('carries what the stack calls the service beside its id', function (): void {
    $about = AboutWhat::theNamedService('qbittorrent', " qBittorrent\n");

    expect(whatItWasCalled($about))->toBe('qBittorrent')
        ->and(whatItWasAbout($about))->toBe('qbittorrent');
});

it('calls nothing by a name the report did not give, rather than by its id', function (): void {
    expect(whatItWasCalled(AboutWhat::theService('gluetun')))->toBe('unsaid')
        ->and(whatItWasCalled(AboutWhat::theMachine()))->toBe('unsaid');
});

it('refuses a name given as nothing, and a named service with no id', function (): void {
    foreach (['', ' ', "\t\n"] as $blank) {
        expect(fn(): AboutWhat => AboutWhat::theNamedService('gluetun', $blank))
            ->toThrow(ServiceIsUnnamed::class, 'gives the name');
    }

    expect(fn(): AboutWhat => AboutWhat::theNamedService(' ', 'Gluetun'))
        ->toThrow(ServiceIsUnnamed::class, 'names none');
});
