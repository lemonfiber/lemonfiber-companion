<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\ARecord;
use Modules\Kernel\Api\AServiceAdopted;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheMoveSaysNothing;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatWasNamed;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** One service adopting would open with a newer version, wanting a copy first or not. */
function aServiceAdopted(string $service, bool $backupFirst): AServiceAdopted
{
    return AServiceAdopted::said(
        WhatAdoptingOneWouldDo::said($service, 'Its database is opened by a newer version', $backupFirst, refused: false),
        '3.0',
        '4.0',
        'newer',
    );
}

/** Which arm a move took, and what it was about, folded to one line. */
function whichWayItMoved(AMove $move): string
{
    return $move->either(
        adopting: static fn(TheAdoption $adoption): TheWordCarriedOut => new TheWordCarriedOut(sprintf('adopting %s', $adoption->project())),
        importing: static fn(TheImport $import): TheWordCarriedOut => new TheWordCarriedOut(sprintf('importing %s', $import->project())),
        standingBeside: static fn(TheStandingBeside $beside): TheWordCarriedOut => new TheWordCarriedOut(sprintf('beside %s', $beside->written())),
        replacing: static fn(TheReplacement $replacement): TheWordCarriedOut => new TheWordCarriedOut(sprintf('replacing %s', $replacement->project())),
    )->said;
}

/**
 * Each way of moving in, as what it came to.
 *
 * @return array<string, array{TheAdoption|TheImport|TheStandingBeside|TheReplacement, MovingInBy, string}>
 */
function everyWayOfMovingIn(): array
{
    return [
        'adopting' => [TheAdoption::of('media', WhatWasNamed::of('back_up'), ''), MovingInBy::Adopting, 'adopting media'],
        'importing' => [TheImport::of('media', TheRecords::of(), TheRecords::of(), WhatIsUnsupported::none()), MovingInBy::Importing, 'importing media'],
        'standing beside' => [TheStandingBeside::of(ThePortsMoved::of(), '/srv/beside.yml'), MovingInBy::StandingBeside, 'beside /srv/beside.yml'],
        'replacing' => [TheReplacement::of('media', WhatWasNamed::of('would_stop'), WhatWasNamed::of('stopped'), WhatWasNamed::of('still_running')), MovingInBy::Replacing, 'replacing media'],
    ];
}

it('says which way of moving in it answers, and hands each to its own arm', function (): void {
    foreach (everyWayOfMovingIn() as $which => [$came, $by, $said]) {
        $move = AMove::at(Stance::Pending, $came);

        expect($move->by())->toBe($by, $which)
            ->and(whichWayItMoved($move))->toBe($said, $which);
    }
});

it('carries the stance as given, and a reason only on a move turned away', function (): void {
    $came = TheStandingBeside::of(ThePortsMoved::of(), '');

    foreach ([Stance::Unchanged, Stance::Pending, Stance::Applied] as $stance) {
        $move = AMove::at($stance, $came);

        expect([$move->stance(), $move->refusal()])->toBe([$stance, '']);
    }

    $blocked = AMove::blocked('There is no single setup here', $came);

    expect([$blocked->stance(), $blocked->refusal()])->toBe([Stance::Blocked, 'There is no single setup here']);
});

it('refuses a move turned away without the stack saying why', function (): void {
    $came = TheStandingBeside::of(ThePortsMoved::of(), '');

    expect(static fn(): AMove => AMove::at(Stance::Blocked, $came))->toThrow(TheMoveSaysNothing::class, '`refusal`')
        ->and(static fn(): AMove => AMove::blocked('  ', $came))->toThrow(TheMoveSaysNothing::class, '`refusal`');
});

it('wants a copy first where any one service does, and not where none does', function (): void {
    $wants = TheAdoption::of('media', WhatWasNamed::of('back_up', '/srv/sonarr'), '/srv/copy.tar', ...['a' => aServiceAdopted('radarr', backupFirst: false), 'b' => aServiceAdopted('sonarr', backupFirst: true)]);
    $services = [];

    foreach ($wants as $upgrade) {
        $services[] = $upgrade->what()->service();
    }

    expect($wants->wantsACopyFirst())->toBeTrue()
        ->and(TheAdoption::of('', WhatWasNamed::of('back_up'), '', aServiceAdopted('radarr', backupFirst: false))->wantsACopyFirst())->toBeFalse()
        ->and(TheAdoption::of('', WhatWasNamed::of('back_up'), '')->wantsACopyFirst())->toBeFalse()
        ->and($services)->toBe(['radarr', 'sonarr'])
        ->and(array_keys(iterator_to_array($wants, preserve_keys: true)))->toBe([0, 1])
        ->and($wants)->toHaveCount(2)
        ->and([$wants->project(), implode(',', iterator_to_array($wants->backUp(), preserve_keys: false)), $wants->backedUp()])->toBe(['media', '/srv/sonarr', '/srv/copy.tar']);
});

it('keeps both versions of a service adopting would open, and refuses a blank one', function (): void {
    $one = aServiceAdopted('sonarr', backupFirst: true);

    expect([$one->what()->service(), $one->existing(), $one->ours(), $one->verdict()])->toBe(['sonarr', '3.0', '4.0', 'newer']);

    $what = WhatAdoptingOneWouldDo::said('sonarr', 'Opened by a newer version', backupFirst: true, refused: false);

    foreach ([['existing', ' ', '4.0', 'newer'], ['ours', '3.0', '', 'newer'], ['verdict', '3.0', '4.0', ' ']] as [$field, $existing, $ours, $verdict]) {
        expect(static fn(): AServiceAdopted => AServiceAdopted::said($what, $existing, $ours, $verdict))
            ->toThrow(TheMoveSaysNothing::class, sprintf('`%s`', $field));
    }
});

it('keeps what an import carried, would carry and could not, and refuses a record that will not say what it is', function (): void {
    $record = ARecord::of('sonarr', 'quality profiles', 'HD-1080p');
    $records = TheRecords::of(...['a' => $record, 'b' => ARecord::of('radarr', 'root folders', '/movies')]);
    $notCarried = WhatIsUnsupported::these(Unsupported::of('sonarr', 'its indexers could not be read'));
    $import = TheImport::of('media', $records, TheRecords::of(), $notCarried);

    expect([$record->service(), $record->kind(), $record->name()])->toBe(['sonarr', 'quality profiles', 'HD-1080p'])
        ->and(array_keys(iterator_to_array($records, preserve_keys: true)))->toBe([0, 1])
        ->and($records)->toHaveCount(2)
        ->and($import->project())->toBe('media')
        ->and($import->carried())->toBe($records)
        ->and($import->wouldCarry())->toHaveCount(0)
        ->and($import->notCarried())->toBe($notCarried);

    foreach ([['service', ' ', 'profiles', 'HD'], ['kind', 'sonarr', '', 'HD'], ['name', 'sonarr', 'profiles', ' ']] as [$field, $service, $kind, $name]) {
        expect(static fn(): ARecord => ARecord::of($service, $kind, $name))->toThrow(TheMoveSaysNothing::class, sprintf('`%s`', $field));
    }
});

it('keeps where each service would listen beside what is here, and the file that says so', function (): void {
    $ports = ThePortsMoved::of(APortMoved::of('sonarr', 8989, 8990));
    $beside = TheStandingBeside::of($ports, '/srv/beside.yml');

    expect($beside->ports())->toBe($ports)
        ->and($beside->written())->toBe('/srv/beside.yml');
});

it('keeps what replacing would stop, stopped, and could not stop', function (): void {
    $replacement = TheReplacement::of(
        'media',
        WhatWasNamed::of('would_stop', 'sonarr'),
        WhatWasNamed::of('stopped', 'radarr'),
        WhatWasNamed::of('still_running', 'tautulli'),
    );

    expect([
        $replacement->project(),
        implode(',', iterator_to_array($replacement->wouldStop(), preserve_keys: false)),
        implode(',', iterator_to_array($replacement->stopped(), preserve_keys: false)),
        implode(',', iterator_to_array($replacement->stillRunning(), preserve_keys: false)),
    ])->toBe(['media', 'sonarr', 'radarr', 'tautulli']);
});

it('names things in the stack\'s order, and refuses a blank one by the field it came from', function (): void {
    $named = WhatWasNamed::of('would_stop', ...['x' => 'sonarr', 'y' => 'radarr']);

    expect(array_keys(iterator_to_array($named, preserve_keys: true)))->toBe([0, 1])
        ->and($named)->toHaveCount(2)
        ->and(static fn(): WhatWasNamed => WhatWasNamed::of('would_stop', 'sonarr', ' '))->toThrow(TheMoveSaysNothing::class, '`would_stop`');
});

it('says a replacement left something running only where something would not stop', function (): void {
    expect(TheReplacement::of('media', WhatWasNamed::of('would_stop'), WhatWasNamed::of('stopped', 'radarr'), WhatWasNamed::of('still_running', 'tautulli'))->leftSomethingRunning())->toBeTrue()
        ->and(TheReplacement::of('media', WhatWasNamed::of('would_stop'), WhatWasNamed::of('stopped', 'radarr'), WhatWasNamed::of('still_running'))->leftSomethingRunning())->toBeFalse();
});
