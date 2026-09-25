<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AMomentInItsHistory;
use Modules\Kernel\Api\AnEpisodeNotHereYet;
use Modules\Kernel\Api\ASeriesCounted;
use Modules\Kernel\Api\AStageItReached;
use Modules\Kernel\Api\HowFarItGot;
use Modules\Kernel\Api\HowMuchOfASeasonIsHere;
use Modules\Kernel\Api\HowMuchOfItIsHere;
use Modules\Kernel\Api\HowSureTheTraceIs;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\TheMomentsInItsHistory;
use Modules\Kernel\Api\TheStagesItReached;
use Modules\Kernel\Api\WhatHappenedToIt;
use Modules\Kernel\Api\WhatTheTraceFound;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Kernel\Api\WhereTheServicesDisagree;

/**
 * One traced series, as the kernel holds it and as a stack sends it.
 *
 * The two halves are written together so a contract test comparing the fake
 * with the adapter compares like with like, and the payload is judged against
 * the contract wherever it is used.
 */
final readonly class TracesToFollow
{
    /** A series stopped in the download client, uncertain, one season short. */
    public static function aSeriesStuckDownloading(): WhereItGotTo
    {
        return WhereItGotTo::followed('Severance', WhatTheTraceFound::traced(
            HowSureTheTraceIs::Uncertain,
            HowFarItGot::reached(
                Stage::Downloading,
                TheStagesItReached::of(
                    AStageItReached::recorded(Stage::Monitored, ServiceId::called('sonarr'), ''),
                    AStageItReached::recorded(Stage::Grabbed, ServiceId::called('sonarr'), '2026-09-20T10:00:00Z'),
                    AStageItReached::recorded(Stage::Downloading, ServiceId::called('qbittorrent'), '2026-09-20T10:01:00Z'),
                ),
                'No peers have been seen for two days',
            ),
            TheMomentsInItsHistory::of(
                AMomentInItsHistory::recorded(WhatHappenedToIt::Grabbed, '2026-09-19T09:00:00Z'),
                AMomentInItsHistory::recorded(WhatHappenedToIt::DownloadFailed, '2026-09-19T21:00:00Z'),
                AMomentInItsHistory::recorded(WhatHappenedToIt::Grabbed, '2026-09-20T10:00:00Z'),
            ),
            WhereTheServicesDisagree::of('Jellyfin holds an episode no service is watching for'),
            HowMuchOfItIsHere::inParts(ASeriesCounted::counted(
                8,
                10,
                1,
                HowMuchOfASeasonIsHere::counted(1, 8, 9, 1, AnEpisodeNotHereYet::numbered(1, 9, 'The We We Are', Stage::Downloading)),
                HowMuchOfASeasonIsHere::counted(2, 0, 1, 0, AnEpisodeNotHereYet::numbered(2, 1, 'Hello, Ms. Cobel', Stage::Searching)),
            )),
        ));
    }

    /**
     * The payload a stack sends for it.
     *
     * @return array<string, mixed>
     */
    public static function whatAStackSaysOfTheSeries(): array
    {
        return ['api_version' => 1, 'kind' => 'trace', 'data' => self::theSeriesAsAStackSendsIt()];
    }

    /**
     * What a stack sends under `data` for it.
     *
     * @return array<string, mixed>
     */
    public static function theSeriesAsAStackSendsIt(): array
    {
        return [
            'item' => 'Severance',
            'matched' => true,
            'confidence' => 'uncertain',
            'furthest' => 'downloading',
            'stall' => 'No peers have been seen for two days',
            'stages' => [
                ['stage' => 'monitored', 'service' => 'sonarr', 'at' => null],
                ['stage' => 'grabbed', 'service' => 'sonarr', 'at' => '2026-09-20T10:00:00Z'],
                ['stage' => 'downloading', 'service' => 'qbittorrent', 'at' => '2026-09-20T10:01:00Z'],
            ],
            'history' => [
                ['outcome' => 'grabbed', 'at' => '2026-09-19T09:00:00Z'],
                ['outcome' => 'download-failed', 'at' => '2026-09-19T21:00:00Z'],
                ['outcome' => 'grabbed', 'at' => '2026-09-20T10:00:00Z'],
            ],
            'findings' => ['Jellyfin holds an episode no service is watching for'],
            'coverage' => [
                'have' => 8,
                'wanted' => 10,
                'unmonitored' => 1,
                'seasons' => [
                    ['season' => 1, 'have' => 8, 'wanted' => 9, 'unmonitored' => 1, 'outstanding' => [
                        ['season' => 1, 'number' => 9, 'title' => 'The We We Are', 'stage' => 'downloading'],
                    ]],
                    ['season' => 2, 'have' => 0, 'wanted' => 1, 'unmonitored' => 0, 'outstanding' => [
                        ['season' => 2, 'number' => 1, 'title' => 'Hello, Ms. Cobel', 'stage' => 'searching'],
                    ]],
                ],
            ],
        ];
    }
}
