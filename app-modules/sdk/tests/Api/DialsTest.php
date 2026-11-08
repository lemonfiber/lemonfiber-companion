<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnEditFoundInTheFile;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatAChangeLeavesAlone;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Sdk\Api\Dials;
use Modules\Sdk\Api\SettingIsUnreadable;

use function sprintf;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

/**
 * Everything a review finds, filled in, with whatever the case under test changes.
 *
 * @param  array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function everythingAReviewFinds(array $overrides = []): array
{
    return [
        'opens' => [
            ['what' => 'A Usenet login', 'because' => 'Usenet needs one', 'setting' => 'USENET_USER'],
            ['what' => 'A Usenet provider', 'because' => 'Nothing downloads without one', 'setting' => null],
            ['what' => 'An account', 'because' => 'The provider asks for one'],
        ],
        'stops' => ['qbittorrent', 'gluetun'],
        'keeps' => ['sabnzbd'],
        'library' => [
            ['service' => 'jellyfin', 'path' => '/media/films', 'host' => '/srv/media/films', 'carried' => true, 'because' => 'It moves with the data'],
            ['service' => 'radarr', 'path' => '/downloads', 'carried' => false, 'because' => 'It is outside the data location'],
        ],
        'edited' => ['wrote' => '/data', 'found' => '/srv', 'secret' => false],
        'active' => [['protocol' => 'torrent', 'name' => 'A film', 'progress' => 40]],
        ...$overrides,
    ];
}

/**
 * A `config` envelope answering a change, with the review and the payload around it as the case says.
 *
 * @param  array<string, mixed> $review
 * @param  array<string, mixed> $data
 * @return Envelope<mixed>
 */
function aChangeAnswered(array $review = [], array $data = []): Envelope
{
    return new Envelope(1, 'config', [
        'changed' => true,
        'rehearsed' => false,
        'settings' => [],
        'review' => [
            'change' => ['key' => 'DATA_ROOT', 'from' => '/data', 'to' => '/srv', 'cost' => 'consequential'],
            'stance' => 'pending',
            ...$review,
        ],
        ...$data,
    ]);
}

/** Every finding of a reading, folded to one line per kind so the order can be read. */
function everyFindingIn(WhereTheChangeStands $stands): string
{
    $comesTo = $stands->comesTo;
    $asks = [];

    foreach ($comesTo->asks as $ask) {
        $asks[] = sprintf('%s/%s/%s', $ask->what, $ask->because, $ask->where(
            kept: static fn(string $setting): TheWordCarriedOut => new TheWordCarriedOut($setting),
            obtained: static fn(): TheWordCarriedOut => new TheWordCarriedOut('obtained'),
        )->said);
    }

    $libraries = [];

    foreach ($comesTo->libraries as $library) {
        $libraries[] = sprintf('%s:%s:%s:%s', $library->service, $library->path, $library->because, $library->carried ? 'carried' : 'left');
    }

    $interrupts = [];

    foreach ($comesTo->interrupts as $download) {
        $interrupts[] = sprintf('%s:%s:%d', $download->name, $download->by->value, $download->progress);
    }

    return implode(' | ', [
        implode(', ', $asks),
        implode(', ', array_map(static fn(ServiceId $service): string => $service->named(), $comesTo->stops)),
        implode(', ', array_map(static fn(WhatAChangeLeavesAlone $left): string => $left->shown(), $comesTo->keeps)),
        implode(', ', $libraries),
        implode(', ', $interrupts),
        whatAReadingSaysWasEdited($comesTo),
        $comesTo->whatItMeans(
            said: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut($said),
            nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no sentence'),
        )->said,
    ]);
}

/** The edit a reading found, both sides, or the word for none. */
function whatAReadingSaysWasEdited(WhatAChangeComesTo $comesTo): string
{
    return $comesTo->theEdit(
        found: static fn(AnEditFoundInTheFile $edit): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s>%s',
            $edit->wrote->either(
                shown: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut($value),
                withheld: static fn(string $note): TheWordCarriedOut => new TheWordCarriedOut(sprintf('withheld %s', $note)),
            )->said,
            $edit->found->either(
                shown: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut($value),
                withheld: static fn(string $note): TheWordCarriedOut => new TheWordCarriedOut(sprintf('withheld %s', $note)),
            )->said,
        )),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('no edit'),
    )->said;
}

it('reads everything a change comes to, in the order the stack found it, with what it means', function (): void {
    $stands = Dials::reviewIn(aChangeAnswered(
        ['findings' => everythingAReviewFinds()],
        ['consequence' => 'Every service restarts on the new location.'],
    ));

    expect(everyFindingIn($stands))->toBe(implode(' | ', [
        'A Usenet login/Usenet needs one/USENET_USER, A Usenet provider/Nothing downloads without one/obtained, An account/The provider asks for one/obtained',
        'qbittorrent, gluetun',
        'sabnzbd',
        'jellyfin:/media/films:It moves with the data:carried, radarr:/downloads:It is outside the data location:left',
        'A film:torrent:40',
        '/data>/srv',
        'Every service restarts on the new location.',
    ]));
});

it('a review with no findings, and no sentence, comes to nothing', function (): void {
    expect(everyFindingIn(Dials::reviewIn(aChangeAnswered())))->toBe(' |  |  |  |  | no edit | no sentence')
        ->and(everyFindingIn(Dials::reviewIn(aChangeAnswered(data: ['consequence' => null]))))->toBe(' |  |  |  |  | no edit | no sentence');
});

it('an edit with no hand-edit found, absent or null, is none', function (): void {
    $absent = everythingAReviewFinds();
    unset($absent['edited']);

    expect(whatAReadingSaysWasEdited(Dials::reviewIn(aChangeAnswered(['findings' => $absent]))->comesTo))->toBe('no edit')
        ->and(whatAReadingSaysWasEdited(Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['edited' => null])]))->comesTo))->toBe('no edit');
});

it('a withheld edit carries the stack\'s notes as withheld, and an empty side as empty', function (): void {
    expect(whatAReadingSaysWasEdited(Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['edited' => ['wrote' => '(set, not shown)', 'found' => '(set, not shown)', 'secret' => true]])]))->comesTo))
        ->toBe('withheld (set, not shown)>withheld (set, not shown)')
        ->and(whatAReadingSaysWasEdited(Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['edited' => ['wrote' => '', 'found' => '/srv', 'secret' => false]])]))->comesTo))
        ->toBe('>/srv');
});

it('carries what it found on a refused change as well as a staged one', function (): void {
    $stands = Dials::reviewIn(aChangeAnswered(
        ['stance' => 'blocked', 'refusal' => 'Something is still downloading.', 'findings' => everythingAReviewFinds()],
        ['changed' => false],
    ));

    expect($stands->why())->toBe('Something is still downloading.')
        ->and(array_map(static fn(ServiceId $service): string => $service->named(), $stands->comesTo->stops))->toBe(['qbittorrent', 'gluetun']);
});

it('refuses findings, or a list of them, that are not what the contract says', function (array $findings, string $said): void {
    expect(fn(): WhereTheChangeStands => Dials::reviewIn(aChangeAnswered(['findings' => $findings])))
        ->toThrow(SettingIsUnreadable::class, $said);
})->with([
    'stops missing' => [(static function (): array {
        $found = everythingAReviewFinds();
        unset($found['stops']);

        return $found;
    })(), 'no `stops`'],
    'keeps not a list' => [everythingAReviewFinds(['keeps' => 'sabnzbd']), 'no `keeps`'],
    'a blank name' => [everythingAReviewFinds(['stops' => ['qbittorrent', ' ']]), 'position 1 of `stops`'],
    'an ask that is not a table' => [everythingAReviewFinds(['opens' => ['a login']]), 'position 0 of `opens`'],
    'an ask with no because' => [everythingAReviewFinds(['opens' => [['what' => 'A login']]]), 'position 0 of `opens`'],
    'an ask kept in a blank setting' => [everythingAReviewFinds(['opens' => [['what' => 'A login', 'because' => 'b', 'setting' => ' ']]]), 'position 0 of `opens`'],
    'a library that is not a table' => [everythingAReviewFinds(['library' => ['/media']]), 'position 0 of `library`'],
    'a library not said to be carried or not' => [everythingAReviewFinds(['library' => [['service' => 's', 'path' => '/m', 'because' => 'r']]]), 'position 0 of `library`'],
    'a download that is not a table' => [everythingAReviewFinds(['active' => ['A film']]), 'position 0 of `active`'],
    'a download with no way named' => [everythingAReviewFinds(['active' => [['protocol' => ' ', 'name' => 'A film', 'progress' => 1]]]), 'position 0 of `active`'],
    'a download past all of it' => [everythingAReviewFinds(['active' => [['protocol' => 'usenet', 'name' => 'A film', 'progress' => 101]]]), 'position 0 of `active`'],
    'a download below none' => [everythingAReviewFinds(['active' => [['protocol' => 'usenet', 'name' => 'A film', 'progress' => -1]]]), 'position 0 of `active`'],
    'an edit that is not a table' => [everythingAReviewFinds(['edited' => 'yes']), 'no `edited`'],
    'an edit with no secret' => [everythingAReviewFinds(['edited' => ['wrote' => 'a', 'found' => 'b']]), 'no `edited`'],
    'an edit with a side that is not text' => [everythingAReviewFinds(['edited' => ['wrote' => 3, 'found' => 'b', 'secret' => false]]), 'no `edited`'],
    'an edit with no found' => [everythingAReviewFinds(['edited' => ['wrote' => 'a', 'secret' => false]]), 'no `edited`'],
]);

it('refuses findings that are not a table, and a sentence that is not text', function (): void {
    expect(fn(): WhereTheChangeStands => Dials::reviewIn(aChangeAnswered(['findings' => 'none'])))
        ->toThrow(SettingIsUnreadable::class, 'no `findings`')
        ->and(fn(): WhereTheChangeStands => Dials::reviewIn(aChangeAnswered(data: ['consequence' => '  '])))
        ->toThrow(SettingIsUnreadable::class, 'no `consequence`');
});

it('reads a body the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('ConfigEnvelope', [
        'api_version' => 1,
        'kind' => 'config',
        'data' => [
            'changed' => true,
            'rehearsed' => false,
            'settings' => [],
            'consequence' => 'Every service restarts on the new location.',
            'review' => [
                'change' => ['key' => 'DATA_ROOT', 'from' => '/data', 'to' => '/srv', 'cost' => 'consequential'],
                'stance' => 'pending',
                'findings' => everythingAReviewFinds(),
            ],
        ],
    ]))->toBe([], "The payload this suite reads is not one a stack would send.\n");
});

it('names an unreadable download by the list and position it sits at', function (): void {
    expect(fn(): WhereTheChangeStands => Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['active' => [
        ['protocol' => 'usenet', 'name' => 'One', 'progress' => 5],
        ['protocol' => 'usenet', 'name' => ' ', 'progress' => 5],
    ]])])))->toThrow(SettingIsUnreadable::class, 'position 1 of `active`');
});

it('reads a download from none of it to all of it', function (): void {
    $interrupts = Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['active' => [
        ['protocol' => 'usenet', 'name' => 'Not started', 'progress' => 0],
        ['protocol' => 'torrent', 'name' => 'All there', 'progress' => 100],
    ]])]))->comesTo->interrupts;

    expect([$interrupts[0]->progress, $interrupts[1]->progress])->toBe([0, 100]);
});

it('reads a download coming a way this app has no word for as a download, and keeps the review', function (): void {
    $stands = Dials::reviewIn(aChangeAnswered(['findings' => everythingAReviewFinds(['active' => [
        ['protocol' => 'carrier-pigeon', 'name' => 'A film', 'progress' => 10],
    ]])]));

    expect($stands->comesTo->interrupts[0]->by)->toBe(HowADownloadComes::SomeOtherWay)
        ->and(array_map(static fn(ServiceId $service): string => $service->named(), $stands->comesTo->stops))->toBe(['qbittorrent', 'gluetun']);
});
