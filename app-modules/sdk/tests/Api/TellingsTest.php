<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Sdk\Api\HouseholdIsUnreadable;
use Modules\Sdk\Api\Tellings;
use Tests\Support\WhatTheContractAccepts;

/**
 * What one member is told, as the sentences a screen prints.
 *
 * @param array<mixed> $data
 *
 * @return list<string>
 */
function whatOneMemberIsTold(array $data): array
{
    $said = [];

    foreach (Tellings::in(new Envelope(1, 'household', $data)) as $sentence) {
        $said[] = $sentence->shown();
    }

    return $said;
}

/**
 * What the member this suite reads may reach, which nothing here looks at.
 *
 * Its own rather than the one beside it, because a module suite is one file
 * loaded on its own as often as it is loaded with its neighbours, and a helper
 * borrowed across two of them is a helper one of the two cannot find.
 *
 * @return array<string, mixed>
 */
function whatAMemberOwedSomethingMayReach(): array
{
    return [
        'administrator' => false,
        'disabled' => false,
        'every_library' => true,
        'libraries' => [],
        'restriction' => 'unrestricted',
        'unrated' => 'let-through',
    ];
}

/**
 * A household of one member, whose row is whatever the case is about.
 *
 * Written out rather than edited into the fixture above, because reaching into
 * a nested array is a reach the analyser cannot type and this file would then
 * be asserting against something nobody can read.
 *
 * @param  array<string, mixed> $row
 * @return array<string, mixed>
 */
function aHouseholdTellingWhose(array $row): array
{
    return [
        'available' => true,
        'findings' => [],
        'members' => [[
            'claimed' => true,
            'requests' => [],
            'access' => whatAMemberOwedSomethingMayReach(),
            ...$row,
        ]],
    ];
}

/**
 * A household payload narrowed to one member, as the core sends one.
 *
 * Every field the contract requires of a member row is written out, including
 * the ones nothing here reads: a fixture holding only what its reader wants is
 * a sample of a payload no stack sends, and a reader tested against that has
 * been tested against nothing.
 *
 * @param  array<mixed> $owed
 * @return array<string, mixed>
 */
function aHouseholdTelling(array $owed): array
{
    return [
        'available' => true,
        'findings' => [],
        'members' => [[
            'name' => 'Robin',
            'claimed' => true,
            'requests' => [],
            'access' => whatAMemberOwedSomethingMayReach(),
            'to_hand_over' => $owed,
        ]],
    ];
}

it('N3-R4 — reads the sentences the core wrote, in the order it wrote them', function (): void {
    $said = whatOneMemberIsTold(aHouseholdTelling([
        'Anything you ask for goes to whoever looks after this house first.',
        'You have two left this month.',
    ]));

    expect($said)->toBe([
        'Anything you ask for goes to whoever looks after this house first.',
        'You have two left this month.',
    ]);
});

it('reads an answer that is not one member\'s as nothing owed to anybody', function (): void {
    // The operator's read of the same endpoint. There is nobody in it to be
    // owed anything, so there is nothing to hand over — which is an answer, and
    // not a refusal.
    expect(whatOneMemberIsTold(['available' => true, 'findings' => [], 'members' => []]))->toBe([]);
});

it('refuses a payload that is not a household at all', function (): void {
    expect(fn(): array => whatOneMemberIsTold([]))->toThrow(HouseholdIsUnreadable::class);

    // The envelope's `data` is whatever arrived rather than the shape the
    // generated type asserts, so a payload that is not a list of fields at all
    // has to be refused here rather than trusted.
    expect(fn(): Sentences => Tellings::in(new Envelope(1, 'household', 'not a payload')))
        ->toThrow(HouseholdIsUnreadable::class);
});

it('refuses a household whose members are not rows', function (): void {
    // Three shapes, because each is refused on its own line: no `members` key,
    // a `members` that is not a list, and a member that is not a row. A reader
    // that let any of them through would hand a screen a reading about nobody.
    expect(fn(): array => whatOneMemberIsTold(['available' => true, 'findings' => []]))
        ->toThrow(HouseholdIsUnreadable::class);

    expect(fn(): array => whatOneMemberIsTold(['available' => true, 'findings' => [], 'members' => 'Robin']))
        ->toThrow(HouseholdIsUnreadable::class);

    expect(fn(): array => whatOneMemberIsTold(['available' => true, 'findings' => [], 'members' => ['Robin']]))
        ->toThrow(HouseholdIsUnreadable::class);
});

it('refuses a member row this app cannot read what they are owed from', function (): void {
    // The contract requires `to_hand_over` of every member, so its absence is a
    // stack this app cannot read rather than a member with nothing waiting —
    // and the two are opposite sentences on a screen.
    expect(fn(): array => whatOneMemberIsTold(aHouseholdTellingWhose(['name' => 'Robin'])))
        ->toThrow(HouseholdIsUnreadable::class);

    expect(fn(): array => whatOneMemberIsTold(aHouseholdTellingWhose([
        'name' => 'Robin',
        'to_hand_over' => 'one sentence, rather than a list of them',
    ])))->toThrow(HouseholdIsUnreadable::class);

    // A sentence that is not text is refused rather than dropped, for the same
    // reason: a member told part of what they are owed has no sign that the
    // rest was there.
    expect(fn(): array => whatOneMemberIsTold(aHouseholdTelling([41])))
        ->toThrow(HouseholdIsUnreadable::class);
});

it('refuses a blank sentence rather than printing a line nobody wrote', function (): void {
    // Refused by `Sentence` rather than here, so one type decides what a blank
    // sentence means. A blank line among real ones reads as something the core
    // said, and a member counting what they were told would count it.
    expect(fn(): Sentence => Sentence::of('   '))->toThrow(SentenceSaysNothing::class);

    expect(fn(): array => whatOneMemberIsTold(aHouseholdTelling(['   '])))
        ->toThrow(SentenceSaysNothing::class);
});

it('reads a payload the contract would accept', function (): void {
    $said = aHouseholdTelling(['You have two left this month.']);

    expect(WhatTheContractAccepts::complaintsAbout('HouseholdEnvelope', [
        'api_version' => 1,
        'kind' => 'household',
        'data' => $said,
    ]))->toBe([], "The payload this suite reads is not one a stack would send.\n");
});
