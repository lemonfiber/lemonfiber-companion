<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Sdk\Api\Stoppages;
use Modules\Sdk\Api\StuckIsUnreadable;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `stuck` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see householdSaying()}'s reason:
 * what is under test is what happens when the wire says something the contract
 * does not allow, which a client honouring the contract could never produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function stuckSaying(array $data): Envelope
{
    return new Envelope(1, 'stuck', $data);
}

/**
 * One stalled item, with every part the reader insists on.
 *
 * @return array<string, mixed>
 */
function aStalledRow(string $title, string $service, string $stage): array
{
    return ['title' => $title, 'service' => $service, 'stage' => $stage];
}

/** One row carried out of `stated()`, since it must hand back an object. */
final readonly class WhatOneStuckRowSaid
{
    public function __construct(public string $said) {}
}

/** Every row of a listing, folded to one string, so the order can be read. */
function everyRowIn(Stalled $stalled): string
{
    $rows = [];

    foreach ($stalled as $one) {
        $rows[] = $one->stated(
            static fn(string $title, ServiceId $service, Stage $stage): WhatOneStuckRowSaid
                => new WhatOneStuckRowSaid(sprintf('%s/%s/%s', $title, $service->named(), $stage->value)),
        )->said;
    }

    return implode(' | ', $rows);
}

it('N2-R9 — reads a listing, keeping the stack\'s order', function (): void {
    $stalled = Stoppages::in(stuckSaying([
        'incomplete' => false,
        'items' => [
            aStalledRow('A film nobody has seen', 'radarr', 'searching'),
            aStalledRow('A series somebody has', 'sonarr', 'downloaded'),
        ],
    ]));

    expect(everyRowIn($stalled))
        ->toBe('A film nobody has seen/radarr/searching | A series somebody has/sonarr/downloaded');
});

it('turns `incomplete` into how much is shown, so one place reads the negation', function (): void {
    // The wire says what is missing and a screen says what you are looking at.
    // Turning it here means `false` never has to be understood as *yes, all of
    // it* by somebody skimming a template.
    expect(Stoppages::in(stuckSaying(['incomplete' => true, 'items' => []]))->howMuchIsShown())
        ->toBe(HowMuchIsShown::SomeOfIt)
        ->and(Stoppages::in(stuckSaying(['incomplete' => false, 'items' => []]))->howMuchIsShown())
        ->toBe(HowMuchIsShown::AllOfIt);
});

it('an empty listing is an answer, and it says so about everything', function (): void {
    $stalled = Stoppages::in(stuckSaying(['incomplete' => false, 'items' => []]));

    expect($stalled->count())->toBe(0)
        ->and($stalled->howMuchIsShown())->toBe(HowMuchIsShown::AllOfIt);
});

it('refuses an envelope whose payload is not a payload at all', function (): void {
    // The generated envelope asserts the payload's shape without checking it,
    // so a `stuck` envelope carrying a sentence reaches this reader typed as
    // the contract's shape and is not one. Refused by name, because everything
    // below it reads subscripts.
    expect(fn(): Stalled => Stoppages::in(new Envelope(1, 'stuck', 'not a payload')))
        ->toThrow(StuckIsUnreadable::class, 'data');
});

it('refuses a payload that carries neither of the two fields', function (): void {
    expect(fn(): Stalled => Stoppages::in(stuckSaying([])))
        ->toThrow(StuckIsUnreadable::class, 'incomplete');
});

it('refuses a listing that will not say how much of it this is', function (): void {
    // The field a screen cannot notice the absence of: a listing missing it
    // renders exactly like a complete one. Defaulting it either way is this app
    // inventing an answer — the reassuring one, or a warning on every screen
    // until somebody removes the warning.
    expect(fn(): Stalled => Stoppages::in(stuckSaying(['items' => []])))
        ->toThrow(StuckIsUnreadable::class, 'incomplete');

    expect(fn(): Stalled => Stoppages::in(stuckSaying(['incomplete' => 'no', 'items' => []])))
        ->toThrow(StuckIsUnreadable::class, 'incomplete');
});

it('refuses a listing with no rows key, and one whose rows are not rows', function (): void {
    expect(fn(): Stalled => Stoppages::in(stuckSaying(['incomplete' => false])))
        ->toThrow(StuckIsUnreadable::class, 'items');

    expect(fn(): Stalled => Stoppages::in(stuckSaying(['incomplete' => false, 'items' => 'none'])))
        ->toThrow(StuckIsUnreadable::class, 'items');
});

it('names the position of the row it refused rather than always the first', function (): void {
    // A good row followed by a bad one is the shortest listing that tells the
    // counter going up from a counter that never moved. Both refusal sites,
    // because they count from the same variable and only one of them is on the
    // path a wrong type takes.
    $each = [
        'a row that is not a row at all' => 'a sentence where an item belongs',
        'a row with no title' => ['service' => 'radarr', 'stage' => 'searching'],
        'a row with a blank title' => aStalledRow('   ', 'radarr', 'searching'),
        'a row with no service' => ['title' => 'A film', 'stage' => 'searching'],
        'a row at a stage this app does not read' => aStalledRow('A film', 'radarr', 'transmuting'),
    ];

    foreach ($each as $second => $row) {
        expect(fn(): Stalled => Stoppages::in(stuckSaying([
            'incomplete' => false,
            'items' => [aStalledRow('A film nobody has seen', 'radarr', 'searching'), $row],
        ])))->toThrow(StuckIsUnreadable::class, 'Item 1', sprintf('second row: %s', $second));
    }
});

it('a stage this app does not recognise names what it does read', function (): void {
    // The accepted list comes from the enum rather than a sentence written in
    // the message, so a case added to the contract cannot leave the refusal
    // describing the old vocabulary.
    expect(fn(): Stalled => Stoppages::in(stuckSaying([
        'incomplete' => false,
        'items' => [aStalledRow('A film', 'radarr', 'transmuting')],
    ])))->toThrow(StuckIsUnreadable::class, '`not-monitored`');
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // Held to the generated types rather than to the reader, because a fixture
    // is written by whoever wrote the reader: where the two agree about a field
    // that is not there, both are wrong in the same direction and every case
    // above is green against a machine nobody has run them against.
    $payload = [
        'incomplete' => false,
        'items' => [aStalledRow('A film nobody has seen', 'radarr', 'searching')],
    ];

    expect(WhatTheContractAccepts::complaintsAbout('StuckEnvelope', ['kind' => 'stuck', 'data' => $payload]))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
