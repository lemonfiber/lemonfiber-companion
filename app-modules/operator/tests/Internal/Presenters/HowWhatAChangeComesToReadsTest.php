<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\ALibraryAMoveReaches;
use Modules\Kernel\Api\AnEditFoundInTheFile;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatAChangeLeavesAlone;
use Modules\Operator\Internal\Presenters\HowWhatAChangeComesToReads;
use Modules\Operator\Internal\ViewModels\ALineOfWhatAChangeComesTo;

/**
 * One line drawn in its own register.
 *
 * @param array<string, int|string> $with
 */
function aFindingLine(string $said, array $with = []): ALineOfWhatAChangeComesTo
{
    return new ALineOfWhatAChangeComesTo($said, $with, aside: false);
}

/**
 * One line drawn as the explanation of the line above it.
 *
 * @param array<string, int|string> $with
 */
function anAsideLine(string $said, array $with = []): ALineOfWhatAChangeComesTo
{
    return new ALineOfWhatAChangeComesTo($said, $with, aside: true);
}

it('a change that comes to nothing draws no line', function (): void {
    expect(new HowWhatAChangeComesToReads()->in(WhatAChangeComesTo::nothing())->lines)->toBe([]);
});

it('draws every finding in order: what it means, what stops, what is interrupted, the libraries, the asks, the edit, and what is kept', function (): void {
    $comesTo = WhatAChangeComesTo::meaning('Every service restarts.')
        ->withTheEdit(AnEditFoundInTheFile::shown('/data', '/srv'))
        ->leaving(WhatAChangeLeavesAlone::said('everything already downloaded'))
        ->asking(WhatAChangeAsksFor::keptIn('A login', 'Usenet needs one', 'USENET_USER'))
        ->interrupting(ADownloadItWouldInterrupt::of('A film', HowADownloadComes::Usenet, 40))
        ->reaching(ALibraryAMoveReaches::of('jellyfin', '/media/films', 'It moves with the data', carried: true))
        ->stopping(ServiceId::called('qbittorrent'))
        ->asking(WhatAChangeAsksFor::toObtain('A provider', 'Nothing downloads without one'))
        ->interrupting(ADownloadItWouldInterrupt::of('A show', HowADownloadComes::Torrent, 0))
        ->reaching(ALibraryAMoveReaches::of('radarr', '/downloads', 'It stays behind', carried: false))
        ->stopping(ServiceId::called('gluetun'));

    expect(new HowWhatAChangeComesToReads()->in($comesTo)->lines)->toEqual([
        aFindingLine('config.findings.in_its_words', ['words' => 'Every service restarts.']),
        aFindingLine('config.findings.stops', ['service' => 'qbittorrent']),
        aFindingLine('config.findings.stops', ['service' => 'gluetun']),
        aFindingLine('config.findings.interrupts.usenet', ['name' => 'A film', 'progress' => 40]),
        aFindingLine('config.findings.interrupts.torrent', ['name' => 'A show', 'progress' => 0]),
        aFindingLine('config.findings.library_carried', ['service' => 'jellyfin', 'path' => '/media/films']),
        anAsideLine('config.findings.in_its_words', ['words' => 'It moves with the data']),
        aFindingLine('config.findings.library_not_carried', ['service' => 'radarr', 'path' => '/downloads']),
        anAsideLine('config.findings.in_its_words', ['words' => 'It stays behind']),
        aFindingLine('config.findings.asks_for', ['what' => 'A login']),
        anAsideLine('config.findings.in_its_words', ['words' => 'Usenet needs one']),
        anAsideLine('config.findings.kept_in', ['setting' => 'USENET_USER']),
        aFindingLine('config.findings.asks_for', ['what' => 'A provider']),
        anAsideLine('config.findings.in_its_words', ['words' => 'Nothing downloads without one']),
        anAsideLine('config.findings.to_obtain'),
        aFindingLine('config.findings.edited'),
        anAsideLine('config.findings.wrote', ['value' => '/data']),
        anAsideLine('config.findings.found', ['value' => '/srv']),
        anAsideLine('config.findings.keeps', ['what' => 'everything already downloaded']),
    ]);
});

it('draws an empty side of an edit and a withheld one each in its own words', function (): void {
    expect(new HowWhatAChangeComesToReads()->in(WhatAChangeComesTo::nothing()->withTheEdit(AnEditFoundInTheFile::shown('', '/srv')))->lines)->toEqual([
        aFindingLine('config.findings.edited'),
        anAsideLine('config.findings.wrote_empty', ['value' => '']),
        anAsideLine('config.findings.found', ['value' => '/srv']),
    ])
        ->and(new HowWhatAChangeComesToReads()->in(WhatAChangeComesTo::nothing()->withTheEdit(AnEditFoundInTheFile::shown('/data', '')))->lines)->toEqual([
            aFindingLine('config.findings.edited'),
            anAsideLine('config.findings.wrote', ['value' => '/data']),
            anAsideLine('config.findings.found_empty', ['value' => '']),
        ])
        ->and(new HowWhatAChangeComesToReads()->in(WhatAChangeComesTo::nothing()->withTheEdit(AnEditFoundInTheFile::withheld('(set)', '(set)')))->lines)->toEqual([
            aFindingLine('config.findings.edited'),
            anAsideLine('config.findings.wrote_withheld'),
            anAsideLine('config.findings.found_withheld'),
        ]);
});

it('draws a download coming a way this app has no word for as a download', function (): void {
    expect(new HowWhatAChangeComesToReads()->in(WhatAChangeComesTo::nothing()->interrupting(ADownloadItWouldInterrupt::of('A film', HowADownloadComes::named('carrier-pigeon'), 10)))->lines)
        ->toEqual([aFindingLine('config.findings.interrupts.another-way', ['name' => 'A film', 'progress' => 10])]);
});
