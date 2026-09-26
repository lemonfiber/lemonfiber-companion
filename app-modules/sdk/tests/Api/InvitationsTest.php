<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Sdk\Api\InvitationIsUnreadable;
use Modules\Sdk\Api\Invitations;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * An `invitation` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function invitationSaying(mixed $data): Envelope
{
    return new Envelope(1, 'invitation', $data);
}

/**
 * A rehearsal for Anna that grants nothing and takes nobody back.
 *
 * @return array<string, mixed>
 */
function aPlainInvitation(): array
{
    return [
        'name' => 'anna',
        'address' => 'http://loft.local:8096',
        'hours' => 72,
        'linked' => 'not-tried',
        'rehearsed' => true,
        'standing' => 'made',
        'withdrawn' => [],
    ];
}

/**
 * What an invitation granted, where it granted something.
 *
 * @return array<string, mixed>
 */
function whatAnInvitationApplied(): array
{
    return [
        'libraries' => ['Films', 'Kids'],
        'limit' => 'PG-13',
        'unrated' => 'held-back',
        'requesting' => 'not-yet',
        'filtering' => 'A limit is not a lock',
    ];
}

/** One line carried out of an arm. */
final readonly class WhatTheInvitationCarried
{
    public function __construct(public string $said) {}
}

/**
 * Everything a payload says, as one line per part.
 *
 * @param array<string, mixed> $data
 */
function everythingTheInvitationSays(array $data): string
{
    $invitation = Invitations::in(invitationSaying($data));
    $toHand = $invitation->toHand();
    $withdrawn = [];

    foreach ($invitation->withdrawn() as $name) {
        $withdrawn[] = $name;
    }

    return implode("\n", [
        sprintf('%s|%s|%s|%d', $toHand->name(), $toHand->address()->url(), $toHand->address()->caution(), $toHand->hours()),
        sprintf('%s|%s|%s', $invitation->standing()->value, $invitation->linked()->value, $invitation->wasRehearsed() ? 'rehearsed' : 'carried out'),
        implode(',', $withdrawn),
        whatTheInvitationGrants($invitation),
    ]);
}

/** What an invitation granted, as one line. */
function whatTheInvitationGrants(AnInvitation $invitation): string
{
    return $invitation->granted(
        these: static function (WhatWasGranted $granted): WhatTheInvitationCarried {
            $libraries = [];

            foreach ($granted->libraries() as $library) {
                $libraries[] = $library;
            }

            return new WhatTheInvitationCarried(sprintf(
                '%s|%s|%s|%s|%s',
                implode(',', $libraries),
                $granted->limit(),
                $granted->unrated()->value,
                $granted->requesting()->value,
                $granted->filtering(),
            ));
        },
        nothing: static fn(): WhatTheInvitationCarried => new WhatTheInvitationCarried('nothing'),
    )->said;
}

it('reads a rehearsal that grants nothing', function (): void {
    expect(everythingTheInvitationSays(aPlainInvitation()))->toBe("anna|http://loft.local:8096||72\nmade|not-tried|rehearsed\n\nnothing");
});

it('reads an invitation carried out, with its caution, what it granted, and who was taken back', function (): void {
    $data = [
        ...aPlainInvitation(),
        'rehearsed' => false,
        'standing' => 'waiting',
        'linked' => 'made',
        'caution' => 'The number can change',
        'withdrawn' => ['bob', 'carol'],
        'applied' => whatAnInvitationApplied(),
    ];

    expect(everythingTheInvitationSays($data))->toBe(
        "anna|http://loft.local:8096|The number can change|72\n"
        . "waiting|made|carried out\n"
        . "bob,carol\n"
        . 'Films,Kids|PG-13|held-back|not-yet|A limit is not a lock',
    );
});

it('reads a caution or an application sent as nothing as none, and a limit sent as nothing or left out as none', function (): void {
    $applied = [...whatAnInvitationApplied(), 'limit' => null];
    $unlimited = array_diff_key(whatAnInvitationApplied(), ['limit' => true]);

    expect(everythingTheInvitationSays([...aPlainInvitation(), 'caution' => null, 'applied' => null]))->toBe("anna|http://loft.local:8096||72\nmade|not-tried|rehearsed\n\nnothing")
        ->and(everythingTheInvitationSays([...aPlainInvitation(), 'applied' => $applied]))->toEndWith('Films,Kids||held-back|not-yet|A limit is not a lock')
        ->and(everythingTheInvitationSays([...aPlainInvitation(), 'applied' => $unlimited]))->toEndWith('Films,Kids||held-back|not-yet|A limit is not a lock');
});

it('refuses a payload that is not a table', function (): void {
    expect(fn(): AnInvitation => Invitations::in(invitationSaying('an invitation')))->toThrow(InvitationIsUnreadable::class, 'no readable `data`');
});

it('refuses a missing or blank field it cannot do without, naming it', function (): void {
    foreach (['name', 'address', 'standing', 'linked'] as $field) {
        expect(fn(): AnInvitation => Invitations::in(invitationSaying(array_diff_key(aPlainInvitation(), [$field => true]))))
            ->toThrow(InvitationIsUnreadable::class, sprintf('no readable `%s`', $field))
            ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), $field => ' '])))
            ->toThrow(InvitationIsUnreadable::class, sprintf('no readable `%s`', $field));
    }
});

it('refuses hours, a rehearsal flag or a list taken back that is not what the contract says', function (): void {
    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'hours' => '72'])))->toThrow(InvitationIsUnreadable::class, 'no readable `hours`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying(array_diff_key(aPlainInvitation(), ['hours' => true]))))->toThrow(InvitationIsUnreadable::class, 'no readable `hours`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'rehearsed' => 'yes'])))->toThrow(InvitationIsUnreadable::class, 'no readable `rehearsed`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying(array_diff_key(aPlainInvitation(), ['rehearsed' => true]))))->toThrow(InvitationIsUnreadable::class, 'no readable `rehearsed`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'withdrawn' => 'bob'])))->toThrow(InvitationIsUnreadable::class, 'no readable `withdrawn`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying(array_diff_key(aPlainInvitation(), ['withdrawn' => true]))))->toThrow(InvitationIsUnreadable::class, 'no readable `withdrawn`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'withdrawn' => ['bob', ' ']])))->toThrow(InvitationIsUnreadable::class, 'no readable `withdrawn`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'withdrawn' => [3]])))->toThrow(InvitationIsUnreadable::class, 'no readable `withdrawn`');
});

it('refuses hours below none, as the kernel would', function (): void {
    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'hours' => -1])))->toThrow(InvitationSaysNothing::class, 'its `hours` at -1');
});

it('refuses a word from a closed set it has no case for, never reading the nearest one', function (): void {
    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'standing' => 'declined'])))
        ->toThrow(InvitationIsUnreadable::class, 'says `standing` is `declined`, and this app reads `made`, `waiting`, `joined`, `reset`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'linked' => 'maybe'])))
        ->toThrow(InvitationIsUnreadable::class, 'says `linked` is `maybe`, and this app reads `made`, `not-yet`, `not-tried`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => [...whatAnInvitationApplied(), 'unrated' => 'blocked']])))
        ->toThrow(InvitationIsUnreadable::class, 'says `unrated` is `blocked`, and this app reads `held-back`, `let-through`')
        ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => [...whatAnInvitationApplied(), 'requesting' => 'maybe']])))
        ->toThrow(InvitationIsUnreadable::class, 'says `requesting` is `maybe`');
});

it('refuses what was granted where it is not a table, or is missing what it owes', function (): void {
    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => 'everything'])))
        ->toThrow(InvitationIsUnreadable::class, 'no readable `applied`');

    foreach (['unrated', 'requesting', 'filtering'] as $field) {
        expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => array_diff_key(whatAnInvitationApplied(), [$field => true])])))
            ->toThrow(InvitationIsUnreadable::class, sprintf('no readable `applied.%s`', $field))
            ->and(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => [...whatAnInvitationApplied(), $field => ' ']])))
            ->toThrow(InvitationIsUnreadable::class, sprintf('no readable `applied.%s`', $field));
    }

    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => [...whatAnInvitationApplied(), 'limit' => ' ']])))
        ->toThrow(InvitationIsUnreadable::class, 'no readable `applied.limit`');
});

it('refuses libraries that are not a list of names', function (): void {
    foreach (['Films', ['Films', ' '], ['Films', 3]] as $libraries) {
        expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => [...whatAnInvitationApplied(), 'libraries' => $libraries]])))
            ->toThrow(InvitationIsUnreadable::class, 'no readable `applied.libraries`');
    }

    expect(fn(): AnInvitation => Invitations::in(invitationSaying([...aPlainInvitation(), 'applied' => array_diff_key(whatAnInvitationApplied(), ['libraries' => true])])))
        ->toThrow(InvitationIsUnreadable::class, 'no readable `applied.libraries`');
});

it('reads only payloads the contract would accept as an invitation', function (): void {
    $full = [...aPlainInvitation(), 'caution' => 'The number can change', 'withdrawn' => ['bob'], 'applied' => whatAnInvitationApplied()];

    expect(WhatTheContractAccepts::complaintsAbout('InvitationEnvelope', ['api_version' => 1, 'kind' => 'invitation', 'data' => aPlainInvitation()]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('InvitationEnvelope', ['api_version' => 1, 'kind' => 'invitation', 'data' => $full]))->toBe([]);
});
