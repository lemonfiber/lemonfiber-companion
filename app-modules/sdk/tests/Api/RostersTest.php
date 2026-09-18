<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\WhatElseIsRunning;
use Modules\Sdk\Api\RosterIsUnreadable;
use Modules\Sdk\Api\Rosters;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `status` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see anAcknowledgementSaying()}'s
 * reason: what is being tested is what happens when the wire says something the
 * contract does not allow, which a client honouring the contract could not
 * produce.
 *
 * @param array<mixed> $data
 *
 * @return Envelope<mixed>
 */
function aRosterSaying(array $data): Envelope
{
    return new Envelope(1, 'status', $data);
}

/**
 * One service, complete, with whatever this case is about changed.
 *
 * Complete means every field the contract requires, `describes` included —
 * which nothing here reads. A fixture short of a required field is a sample of
 * a payload no stack sends, and a reader tested only against it has been tested
 * against nothing.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aServiceSaying(array $differently = []): array
{
    return [...[
        'id' => 'sonarr',
        'name' => 'Sonarr',
        'describes' => 'Fetches the series somebody is following',
        'profile' => 'downloads',
        'state' => 'running',
        'criticality' => 'important',
        'depends_on' => [],
    ], ...$differently];
}

/**
 * What the one service in a listing exited with, whichever arm it takes.
 *
 * The empty string is *it said nothing*, which is the fact and not a stand-in
 * for one: a service that is running has no exit code at all, and `0` is the
 * code for one that ended well.
 *
 * The carrier is anonymous because this file's namespace is one the
 * architecture rules resolve classes from, and a second named class here maps
 * to no file of its own.
 *
 * @param array<mixed> $differently
 */
function whatTheServiceExitedWith(array $differently): string
{
    $said = '';

    foreach (Rosters::in(aRosterSaying($differently)) as $daemon) {
        $said = $daemon->exit(
            said: static fn(int $code): object => new readonly class ((string) $code) {
                public function __construct(public string $said) {}
            },
            unstated: static fn(): object => new readonly class ('') {
                public function __construct(public string $said) {}
            },
        )->said;
    }

    return $said;
}

/**
 * What a stack reports its verbs cost.
 *
 * Its own helper because every roster carries one: the lengths are read off how
 * long a stack is prepared to wait, so a machine running nothing answers the
 * same as one running eight.
 *
 * @return array<string, mixed>
 */
function whatTheseVerbsCostOnTheWire(): array
{
    return [
        'starting' => ['bound' => 'bounded', 'seconds' => 180],
        'stopping' => ['bound' => 'bounded', 'seconds' => 10],
        'restarting' => ['bound' => 'bounded', 'seconds' => 180],
        'stopping_after_downloads' => ['bound' => 'open-ended', 'until' => 'downloads'],
        'switching' => ['bound' => 'bounded', 'seconds' => 180],
    ];
}

/**
 * A whole listing of one service, with whatever this case is about changed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aRosterOf(array $differently = []): array
{
    return [
        'condition' => 'active',
        'forms' => ['downloads'],
        'disturbs' => whatTheseVerbsCostOnTheWire(),
        'services' => [aServiceSaying($differently)],
        // A container the machine is running that this stack's own
        // configuration does not declare. Every stack sends the field, and it
        // carries a row rather than none so that the shape of a row is part of
        // what the contract is asked about here.
        'undeclared' => [[
            'id' => 'a-container-somebody-started',
            'describes' => 'Something running beside the stack',
            'state' => 'running',
        ]],
    ];
}

/**
 * One container the machine is running that this stack did not put there.
 *
 * `describes` is plain language rather than an image reference — the contract
 * calls it *what it does for the operator, which is exactly what is not known*
 * — so the stand-in says what a stack says and not what a reader assumed.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function somethingElseSaying(array $differently = []): array
{
    return [...[
        'id' => 'pihole',
        'describes' => 'Not declared by this stack',
        'state' => 'running',
    ], ...$differently];
}

it('reads what the stack says it all amounts to', function (): void {
    $daemons = Rosters::in(aRosterSaying(aRosterOf()));

    expect($daemons->running())->toBe(HowTheStackIsRunning::Active);
    expect($daemons->count())->toBe(1);
    expect($daemons->forms()->count())->toBe(1);
});

it('reads a form nothing in it is running', function (): void {
    // The forms come from the stack's own list rather than from the rows, so a
    // form with everything stopped survives the reading. It is the one form an
    // operator opens the app to start.
    $daemons = Rosters::in(aRosterSaying([
        'condition' => 'inactive',
        'disturbs' => whatTheseVerbsCostOnTheWire(),
        'forms' => ['media', 'downloads'],
        'services' => [],
    ]));

    expect($daemons->forms()->count())->toBe(2);
    expect($daemons->count())->toBe(0);
});

it('reads a service that ended by the constructor that says so', function (): void {
    expect(whatTheServiceExitedWith(aRosterOf(['state' => 'failed', 'exit' => 137])))->toBe('137');
});

it('reads a service that is still going as one that said nothing', function (): void {
    // The other arm, and the one that must not be a number: a service running
    // now has no exit code, and a zero here would read as one that ended well.
    expect(whatTheServiceExitedWith(aRosterOf()))->toBe('');
});

it('reads `exit: null` as a service that did not end', function (): void {
    // Absent and null are the same fact said two ways, and the contract allows
    // both. Neither is a service that exited with nothing.
    $daemons = Rosters::in(aRosterSaying(aRosterOf(['exit' => null])));

    foreach ($daemons as $daemon) {
        expect($daemon->runs())->toBe(HowAServiceRuns::Running);
    }

    expect($daemons->count())->toBe(1);
});

it('refuses a list the stack sent as something other than a list', function (): void {
    // Present and not a list is a different fault from absent, and the reader
    // must not tell them apart by luck: `foreach` over a string raises where
    // `foreach` over an array does not, so a reader that only checked for the
    // key would fail somewhere further in, about something else, with the
    // stack's real mistake nowhere in the message.
    //
    // All three lists, because the guard is one method and a caller that
    // reached it by a different road is a caller it has never been asked about.
    $each = [
        'forms as a word' => ['condition' => 'active', 'forms' => 'downloads', 'disturbs' => whatTheseVerbsCostOnTheWire(), 'services' => []],
        'services as a number' => ['condition' => 'active', 'forms' => [], 'services' => 7],
        'depends_on as a word' => [
            'condition' => 'active',
            'forms' => ['downloads'],
            'services' => [aServiceSaying(['depends_on' => 'gluetun'])],
        ],
    ];

    foreach ($each as $which => $said) {
        expect(fn(): object => Rosters::in(aRosterSaying($said)))
            ->toThrow(RosterIsUnreadable::class, 'not what the contract says it is', $which);
    }
});

it('refuses an `exit` that is neither absent, null, nor a number', function (): void {
    // Refused rather than read as still running, which is the reading that
    // turns a service that died into one nobody looks at.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['exit' => 'oom']))))
        ->toThrow(RosterIsUnreadable::class, 'exit');
});

it('refuses a listing with no condition at all', function (): void {
    // Read before any row is, and refused rather than worked out from the rows:
    // a second opinion assembled on a phone would disagree with the machine the
    // first time lemonfiber changed how it weighs a degraded service.
    expect(fn(): object => Rosters::in(aRosterSaying(['disturbs' => whatTheseVerbsCostOnTheWire(), 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'condition');
});

it('refuses a condition that is not a word', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['disturbs' => whatTheseVerbsCostOnTheWire(), 'condition' => 41, 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'condition');
});

it('refuses a condition this build has not heard of', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['disturbs' => whatTheseVerbsCostOnTheWire(), 'condition' => 'brilliant', 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, 'brilliant');
});

it('names what it does read, in the words the enum has', function (): void {
    // The accepted list comes from `cases()` rather than from a sentence, so a
    // word added to the contract cannot leave the message describing the
    // vocabulary of the build before it.
    //
    // Quoted, and asserted quoted. The message is prose with a list inside it,
    // and `degraded` bare in a sentence is a word a reader has to work out the
    // status of — which is the whole difference between a message naming a
    // vocabulary and one describing a machine.
    expect(fn(): object => Rosters::in(aRosterSaying(['disturbs' => whatTheseVerbsCostOnTheWire(), 'condition' => 'brilliant', 'forms' => [], 'services' => []])))
        ->toThrow(RosterIsUnreadable::class, sprintf('`%s`', HowTheStackIsRunning::Degraded->value));
});

it('refuses a state this build has not heard of', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['state' => 'thinking']))))
        ->toThrow(RosterIsUnreadable::class, 'thinking');
});

it('refuses a criticality this build has not heard of', function (): void {
    // How much a service matters decides how loudly a stop is asked about, so
    // an unrecognised word must not become the quietest one.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['criticality' => 'whatever']))))
        ->toThrow(RosterIsUnreadable::class, HowMuchItMatters::Critical->value);
});

it('refuses a service with no id', function (): void {
    $row = aServiceSaying();
    unset($row['id']);

    expect(fn(): object => Rosters::in(aRosterSaying([
        'disturbs' => whatTheseVerbsCostOnTheWire(),
        'condition' => 'active',
        'forms' => [],
        'services' => [$row],
    ])))->toThrow(RosterIsUnreadable::class, 'id');
});

it('names which service it could not read', function (): void {
    // The position is only knowable here, and a refusal saying *service 1* can
    // be acted on where one saying *a service* leaves somebody reading forty.
    expect(fn(): object => Rosters::in(aRosterSaying([
        'disturbs' => whatTheseVerbsCostOnTheWire(),
        'condition' => 'active',
        'forms' => [],
        'services' => [aServiceSaying(), aServiceSaying(['name' => '  '])],
    ])))->toThrow(RosterIsUnreadable::class, 'Service 1');
});

it('refuses a row that is not a service at all', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying([
        'disturbs' => whatTheseVerbsCostOnTheWire(),
        'condition' => 'active',
        'forms' => [],
        'services' => ['a sentence where a service belongs'],
    ])))->toThrow(RosterIsUnreadable::class, 'Service 0');
});

it('refuses a form that is not a name', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying([
        'condition' => 'active',
        'forms' => ['media', ''],
        'services' => [],
    ])))->toThrow(RosterIsUnreadable::class, 'Form 1');
});

it('refuses a name that is only spacing, in both lists that read one', function (): void {
    // `''` and `'   '` are one name on a screen and two values on the wire.
    // Both lists decide after trimming, so a form or a dependency padded into
    // looking like a word is refused here rather than becoming a `Form` or a
    // `ServiceId` that renders as nothing everywhere it is shown — a row
    // leaning on a blank understates what a stop disturbs, which is the reading
    // `leaning()`'s own message says it exists to prevent.
    $each = [
        'a form' => [
            ['condition' => 'active', 'forms' => ['media', '   '], 'services' => []],
            'Form 1',
        ],
        'a dependency' => [
            aRosterOf(['depends_on' => ['jellyfin', "\t"]]),
            'depends_on',
        ],
    ];

    foreach ($each as $which => [$said, $named]) {
        expect(fn(): object => Rosters::in(aRosterSaying($said)))
            ->toThrow(RosterIsUnreadable::class, $named, $which);
    }
});

it('refuses a dependency that is not a name', function (): void {
    // What leans on a service is the sentence put in front of stopping it, so a
    // name that cannot be read would understate what a stop disturbs.
    expect(fn(): object => Rosters::in(aRosterSaying(aRosterOf(['depends_on' => [41]]))))
        ->toThrow(RosterIsUnreadable::class, 'depends_on');
});

it('reads what leans on a service', function (): void {
    $daemons = Rosters::in(aRosterSaying(aRosterOf(['depends_on' => ['jellyfin', 'prowlarr']])));

    foreach ($daemons as $daemon) {
        expect($daemon->whatLeansOnIt()->count())->toBe(2);
    }

    expect($daemons->count())->toBe(1);
});

it('refuses a listing with no services field at all', function (): void {
    expect(fn(): object => Rosters::in(aRosterSaying(['disturbs' => whatTheseVerbsCostOnTheWire(), 'condition' => 'active', 'forms' => []])))
        ->toThrow(RosterIsUnreadable::class, 'services');
});

it('refuses a payload that is not a shape at all', function (): void {
    expect(fn(): object => Rosters::in(new Envelope(1, 'status', 'a sentence where a payload belongs')))
        ->toThrow(RosterIsUnreadable::class, 'data');
});

it('N2-R21 — reads what else the machine is running', function (): void {
    // A second reading of the same envelope, because what the stack did not put
    // there is not part of the stack and the two collections cannot hold each
    // other's rows.
    $else = Rosters::whatElseIsRunning(aRosterSaying([
        ...aRosterOf(),
        'undeclared' => [somethingElseSaying(), somethingElseSaying([
            'id' => 'watchtower',
            'describes' => 'Not declared by this stack',
            'state' => 'healthy',
        ])],
    ]));

    expect($else->count())->toBe(2);

    $first = iterator_to_array($else, preserve_keys: false)[0];

    expect($first->id->named())->toBe('pihole')
        ->and($first->describes)->toBe('Not declared by this stack')
        ->and($first->runs)->toBe(HowAServiceRuns::Running);
});

it('N2-R21 — a machine running only what the stack declares says so', function (): void {
    // Empty is the ordinary answer rather than an absence. A machine with
    // nothing unaccounted for is the expected shape, and reading it as *the
    // stack did not say* would put a question on the screen where there is none.
    //
    // The list is emptied here rather than left to the helper: `aRosterOf()`
    // carries a container so that the contract stand-in is asked about the
    // shape of a row, and this case is the one machine that has none.
    expect(Rosters::whatElseIsRunning(aRosterSaying([...aRosterOf(), 'undeclared' => []]))->isEmpty())->toBeTrue();
});

it('N2-R21 — refuses a payload that is not a shape at all', function (): void {
    // The same refusal {@see Rosters::in} makes, asked of the second reading.
    // Both readings open the same envelope, and a `data` that is a sentence is
    // unreadable whichever question is being put to it.
    expect(fn(): WhatElseIsRunning => Rosters::whatElseIsRunning(
        new Envelope(1, 'status', 'a sentence where a payload belongs'),
    ))->toThrow(RosterIsUnreadable::class, 'data');
});

it('N2-R21 — refuses an undeclared entry that is not a row', function (): void {
    // A list of the right name holding the wrong thing. Refused by the field
    // that carried it rather than read as a container with nothing to say,
    // because a sentence where a row belongs is the machine disagreeing with
    // the contract, not a stranger this screen should render blank.
    expect(fn(): WhatElseIsRunning => Rosters::whatElseIsRunning(aRosterSaying([
        ...aRosterOf(),
        'undeclared' => ['a sentence where a container belongs'],
    ])))->toThrow(RosterIsUnreadable::class, 'undeclared');
});

it('N2-R21 — names the first row where the machine keyed the list by name', function (): void {
    // The reading takes a row's position from its key, which is what the
    // contract's list gives it. A machine that sent an object instead has no
    // position to give, and the reader falls back to the first — so a refusal
    // still names a row rather than reading `somebody-elses-name` as one.
    //
    // Held to the number rather than to the class, because the number is the
    // whole of what the fallback decides: a refusal that named the key, or the
    // row after it, or the one before, would point an operator at a row that is
    // not the one the machine got wrong.
    expect(fn(): WhatElseIsRunning => Rosters::whatElseIsRunning(aRosterSaying([
        ...aRosterOf(),
        'undeclared' => ['somebody-elses-name' => ['id' => 'pihole', 'state' => 'running']],
    ])))->toThrow(RosterIsUnreadable::class, 'Service 0');
});

it('N2-R21 — refuses a container the machine named but did not describe', function (): void {
    // `describes` is what the requirement means by *state what it is running*,
    // so a row without one cannot answer it. Refused by name rather than shown
    // blank, because a row that names a container and says nothing about it is
    // the question the screen exists to answer, left unanswered.
    expect(fn(): WhatElseIsRunning => Rosters::whatElseIsRunning(aRosterSaying([
        ...aRosterOf(),
        'undeclared' => [['id' => 'pihole', 'state' => 'running']],
    ])))->toThrow(RosterIsUnreadable::class);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    // Held to the generated types rather than to the reader, because a fixture
    // is written by whoever wrote the reader: where the two agree about a field
    // that is not there, both are wrong in the same direction and every case
    // above is green against a machine nobody has run them against.
    expect(WhatTheContractAccepts::complaintsAbout('StatusEnvelope', ['kind' => 'status', 'data' => aRosterOf()]))
        ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
});
