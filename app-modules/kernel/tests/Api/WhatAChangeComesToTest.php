<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\AFindingSaysNothing;
use Modules\Kernel\Api\ALibraryAMoveReaches;
use Modules\Kernel\Api\AnEditFoundInTheFile;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\HowFarADownloadIs;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatAChangeLeavesAlone;
use RuntimeException;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** What the stack says applying the change means, or the word for nothing. */
function whatTheChangeMeans(WhatAChangeComesTo $comesTo): string
{
    return $comesTo->whatItMeans(
        said: static fn(string $said): TheWordCarriedOut => new TheWordCarriedOut($said),
        nothing: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
    )->said;
}

/** The edit found in the file, as what lemonfiber wrote and what is there, or the word for none. */
function theEditFound(WhatAChangeComesTo $comesTo): string
{
    return $comesTo->theEdit(
        found: static fn(AnEditFoundInTheFile $edit): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s, %s',
            $edit->wrote->either(
                shown: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut(sprintf('wrote %s', $value)),
                withheld: static fn(string $note): TheWordCarriedOut => new TheWordCarriedOut(sprintf('wrote withheld %s', $note)),
            )->said,
            $edit->found->either(
                shown: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut(sprintf('found %s', $value)),
                withheld: static fn(string $note): TheWordCarriedOut => new TheWordCarriedOut(sprintf('found withheld %s', $note)),
            )->said,
        )),
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
    )->said;
}

/** Where an ask's answer is kept, or the word for an account to obtain. */
function whereTheAnswerIsKept(WhatAChangeAsksFor $ask): string
{
    return $ask->where(
        kept: static fn(string $setting): TheWordCarriedOut => new TheWordCarriedOut($setting),
        obtained: static fn(): TheWordCarriedOut => new TheWordCarriedOut('obtained'),
    )->said;
}

it('a change found to come to nothing carries nothing, no edit and no sentence', function (): void {
    $nothing = WhatAChangeComesTo::nothing();

    expect([$nothing->asks, $nothing->stops, $nothing->keeps, $nothing->libraries, $nothing->interrupts])->toBe([[], [], [], [], []])
        ->and(whatTheChangeMeans($nothing))->toBe('nothing')
        ->and(theEditFound($nothing))->toBe('none');
});

it('carries everything the stack found, in the order it found it', function (): void {
    $ask = WhatAChangeAsksFor::toObtain('A Usenet provider', 'Usenet needs one');
    $library = ALibraryAMoveReaches::of('Jellyfin', '/media/films', 'It moves with the data', carried: true);
    $download = ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Torrent, 40);

    $qbittorrent = ServiceId::called('qbittorrent');
    $gluetun = ServiceId::called('gluetun');
    $left = WhatAChangeLeavesAlone::said('everything already downloaded');

    $found = WhatAChangeComesTo::nothing()
        ->stopping($qbittorrent)
        ->interrupting($download)
        ->asking($ask)
        ->leaving($left)
        ->reaching($library)
        ->stopping($gluetun);

    expect($found->asks)->toBe([$ask])
        ->and($found->stops)->toBe([$qbittorrent, $gluetun])
        ->and($found->keeps)->toBe([$left])
        ->and($found->libraries)->toBe([$library])
        ->and($found->interrupts)->toBe([$download])
        ->and(whatTheChangeMeans($found))->toBe('nothing')
        ->and(theEditFound($found))->toBe('none');
});

it('carries the edit found in the file and the sentence on what it means, each beside the other', function (): void {
    $found = WhatAChangeComesTo::meaning('  Every paired phone refuses this machine until paired again.  ')
        ->withTheEdit(AnEditFoundInTheFile::shown('/data', '/srv'));

    expect(theEditFound($found))->toBe('wrote /data, found /srv')
        ->and(whatTheChangeMeans($found))->toBe('Every paired phone refuses this machine until paired again.')
        ->and(theEditFound(WhatAChangeComesTo::meaning('It means this.')->withTheEdit(AnEditFoundInTheFile::withheld('(set)', '(set)'))))
        ->toBe('wrote withheld (set), found withheld (set)');
});

it('keeps what it found and what it means as each finding is added', function (): void {
    $qbittorrent = ServiceId::called('qbittorrent');
    $edit = AnEditFoundInTheFile::shown('a', 'b');
    $ask = WhatAChangeAsksFor::toObtain('A provider', 'Usenet needs one');
    $left = WhatAChangeLeavesAlone::said('the library');
    $library = ALibraryAMoveReaches::of('Jellyfin', '/m', 'r', carried: true);
    $download = ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Usenet, 1);

    $found = WhatAChangeComesTo::meaning('It means this.')
        ->withTheEdit($edit)
        ->stopping($qbittorrent)
        ->asking($ask)
        ->leaving($left)
        ->reaching($library)
        ->interrupting($download);

    expect([$found->asks, $found->stops, $found->keeps, $found->libraries, $found->interrupts])->toBe([[$ask], [$qbittorrent], [$left], [$library], [$download]])
        ->and(whatTheChangeMeans($found))->toBe('It means this.')
        ->and(theEditFound($found))->toBe('wrote a, found b');
});

it('refuses a sentence on what it means that says nothing', function (): void {
    expect(fn(): WhatAChangeComesTo => WhatAChangeComesTo::meaning('   '))
        ->toThrow(AFindingSaysNothing::class, '`consequence`');
});

it('an ask is kept in a setting, trimmed, or is an account to obtain', function (): void {
    $kept = WhatAChangeAsksFor::keptIn('  A login  ', '  Usenet needs one  ', '  USENET_USER  ');

    expect($kept->what)->toBe('A login')
        ->and($kept->because)->toBe('Usenet needs one')
        ->and(whereTheAnswerIsKept($kept))->toBe('USENET_USER')
        ->and(whereTheAnswerIsKept(WhatAChangeAsksFor::toObtain('A provider', 'Usenet needs one')))->toBe('obtained');
});

it('refuses an ask with a part that says nothing', function (callable $asking, string $part): void {
    expect($asking)->toThrow(AFindingSaysNothing::class, sprintf('`%s`', $part));
})->with([
    'no what' => [static fn(): WhatAChangeAsksFor => WhatAChangeAsksFor::toObtain(' ', 'b'), 'what'],
    'no because' => [static fn(): WhatAChangeAsksFor => WhatAChangeAsksFor::toObtain('a', ' '), 'because'],
    'no setting' => [static fn(): WhatAChangeAsksFor => WhatAChangeAsksFor::keptIn('a', 'b', ' '), 'setting'],
]);

it('a library carries its service and reason trimmed, its path as given, and whether it is carried', function (): void {
    $library = ALibraryAMoveReaches::of('  Jellyfin  ', ' /media/films', '  It stays behind  ', carried: false);

    expect($library->service)->toBe('Jellyfin')
        ->and($library->path)->toBe(' /media/films')
        ->and($library->because)->toBe('It stays behind')
        ->and($library->carried)->toBeFalse()
        ->and(ALibraryAMoveReaches::of('Jellyfin', '/m', 'r', carried: true)->carried)->toBeTrue();
});

it('refuses a library with a part that says nothing', function (string $service, string $path, string $because, string $part): void {
    expect(fn(): ALibraryAMoveReaches => ALibraryAMoveReaches::of($service, $path, $because, carried: true))
        ->toThrow(AFindingSaysNothing::class, sprintf('`%s`', $part));
})->with([
    'no service' => [' ', '/m', 'r', 'service'],
    'no path' => ['s', ' ', 'r', 'path'],
    'no because' => ['s', '/m', ' ', 'because'],
]);

it('a download carries its name, how it comes, and its progress, from none to all of it', function (): void {
    $none = ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Usenet, 0);
    $all = ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Torrent, HowFarADownloadIs::ALL_OF_IT);

    expect([$none->name, $none->by, $none->progress])->toBe(['A film', HowADownloadComes::Usenet, 0])
        ->and([$all->by, $all->progress])->toBe([HowADownloadComes::Torrent, 100]);
});

it('refuses a download with no name, or progress outside none to all of it', function (string $name, int $progress, string $said): void {
    expect(fn(): ADownloadItWouldInterrupt => ADownloadItWouldInterrupt::of($name, HowADownloadComes::Torrent, $progress))
        ->toThrow(AFindingSaysNothing::class, $said);
})->with([
    'no name' => [' ', 10, '`name`'],
    'below none' => ['A film', -1, '-1%'],
    'past all of it' => ['A film', 101, '101%'],
]);

it('names how a download comes as a catalogue key', function (): void {
    expect(HowADownloadComes::Usenet->saidOnTheScreen())->toBe('config.findings.interrupts.usenet')
        ->and(HowADownloadComes::Torrent->saidOnTheScreen())->toBe('config.findings.interrupts.torrent')
        ->and(HowADownloadComes::SomeOtherWay->saidOnTheScreen())->toBe('config.findings.interrupts.another-way');
});

it('knows the core\'s words usenet and torrent, and reads any other word as a download', function (): void {
    expect(HowADownloadComes::named('usenet'))->toBe(HowADownloadComes::Usenet)
        ->and(HowADownloadComes::named('torrent'))->toBe(HowADownloadComes::Torrent)
        ->and(HowADownloadComes::named('carrier-pigeon'))->toBe(HowADownloadComes::SomeOtherWay)
        ->and(HowADownloadComes::cases())->toBe([HowADownloadComes::Usenet, HowADownloadComes::Torrent, HowADownloadComes::SomeOtherWay]);
});

it('a download could be anywhere from none of it to all of it, and nowhere else', function (): void {
    $refused = new RuntimeException('not that far');

    expect(HowFarADownloadIs::at(0, $refused)->share)->toBe(0)
        ->and(HowFarADownloadIs::at(HowFarADownloadIs::ALL_OF_IT, $refused)->share)->toBe(100)
        ->and(fn(): HowFarADownloadIs => HowFarADownloadIs::at(-1, $refused))->toThrow($refused)
        ->and(fn(): HowFarADownloadIs => HowFarADownloadIs::at(101, $refused))->toThrow($refused);
});

it('a thing left as it is is said trimmed, and one that says nothing is refused', function (): void {
    expect(WhatAChangeLeavesAlone::said('  the library  ')->shown())->toBe('the library')
        ->and(fn(): WhatAChangeLeavesAlone => WhatAChangeLeavesAlone::said('  '))->toThrow(AFindingSaysNothing::class, '`keeps`');
});
