<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatLeansOnIt;

use function ucfirst;

/**
 * A machine with services on it, as the screens about them read one.
 *
 * There are two frames — the list of what a machine runs and the one thing
 * behind a row of it — and both need the same listing to be about. Written
 * twice, the two files drift: a state changed in one and not the other is two
 * suites agreeing with themselves and with nothing else, and the screens they
 * cover are the pair an operator moves between.
 *
 * Here rather than in either file, which is what `G10` asks for where a helper
 * is genuinely shared: the root suites share one namespace, so a second copy
 * under a second name is the only other way to have it in both.
 */
final readonly class WhatAMachineRuns
{
    /** What a stack reports its verbs cost. */
    public static function whatTheVerbsCost(): Disturbances
    {
        return Disturbances::of(
            starting: WhatItTakesAway::atMost(180),
            stopping: WhatItTakesAway::atMost(10),
            restarting: WhatItTakesAway::atMost(180),
        );
    }

    /** One service, with whatever a case is about changed. */
    public static function aService(
        string $id = 'sonarr',
        HowAServiceRuns $runs = HowAServiceRuns::Running,
        ?WhatLeansOnIt $leaning = null,
    ): Daemon {
        return Daemon::called(
            ucfirst($id),
            ServiceId::called($id),
            $runs,
            HowMuchItMatters::Important,
            $leaning ?? WhatLeansOnIt::nothing(),
        );
    }

    /**
     * Two services, one of them leaned on by the other, on a stack declaring
     * two forms.
     */
    public static function twoThings(): Daemons
    {
        return Daemons::of(
            HowTheStackIsRunning::Active,
            Forms::these(Form::called('library'), Form::called('full')),
            self::whatTheVerbsCost(),
            self::aService('sonarr', leaning: WhatLeansOnIt::these(ServiceId::called('jellyfin'))),
            self::aService('jellyfin'),
        );
    }

    /** One service, on a stack declaring one form, however that one is running. */
    public static function oneThing(string $id, HowAServiceRuns $runs, HowTheStackIsRunning $overall): Daemons
    {
        return Daemons::of(
            $overall,
            Forms::these(Form::called('library')),
            self::whatTheVerbsCost(),
            self::aService($id, $runs),
        );
    }

    /**
     * Part of a stack running on purpose: `library` and `hunt` asked for,
     * Jellyfin running for both, Sonarr for `hunt`, and qBittorrent left out
     * of `hunt` for want of torrent credentials, which the stack also lists
     * among the services as absent.
     */
    public static function partOfItOnPurpose(): Daemons
    {
        return Daemons::of(
            HowTheStackIsRunning::Active,
            Forms::these(Form::called('library'), Form::called('hunt'), Form::called('full')),
            self::whatTheVerbsCost(),
            self::aService('jellyfin')->broughtInBy(Forms::these(Form::called('library'), Form::called('hunt'))),
            self::aService('sonarr')->broughtInBy(Forms::these(Form::called('hunt'))),
            self::aService('qbittorrent', HowAServiceRuns::Absent),
        )->asked(
            Forms::these(Form::called('library'), Form::called('hunt')),
            TheServicesLeftOut::of(AServiceLeftOut::needing(
                ServiceId::called('qbittorrent'),
                'qBittorrent',
                WhatItWouldNeed::Torrent,
                Forms::these(Form::called('hunt')),
            )),
        );
    }
}
