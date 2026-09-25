<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\AWalkthrough;
use Modules\Kernel\Api\HowTheImportLinked;
use Modules\Kernel\Api\TheLinesItSaid;
use Modules\Kernel\Api\WalkthroughStep;
use Modules\Kernel\Api\WhatComesNext;
use Modules\Kernel\Api\WhatCouldBeWalkedInstead;
use Modules\Kernel\Api\WhatTheServicesWereSaying;
use Modules\Kernel\Api\WhatToDoNext;
use Modules\Kernel\Api\WhatWasWalked;
use Modules\Kernel\Api\WhereItStopped;
use Modules\Kernel\Api\WhereTheWalkthroughIs;
use Modules\Kernel\Api\WhichWalk;
use Modules\Kernel\Api\WhyTheWalkthroughStopped;

/**
 * Three walkthroughs, each as the kernel holds it and as a stack sends it.
 *
 * The two halves are written together so a contract test comparing the fake
 * with the adapter compares like with like, and each payload is judged
 * against the contract wherever it is used.
 *
 * The finished walk says its steps out of the order the steps are declared
 * in — it searched before it chose — so a reader or a screen that sorted the
 * lines by step would be caught.
 */
final readonly class WalkthroughsToFollow
{
    /** A walk that worked, copied rather than hardlinked, handing over to all three. */
    public static function aWalkThatWorked(): AWalkthrough
    {
        return AWalkthrough::reported(
            WhichWalk::Pipeline,
            WhereTheWalkthroughIs::Complete,
            'That every link from the indexers to the library works.',
            WhatWasWalked::called('Big Buck Bunny'),
            TheLinesItSaid::of(
                ALineItSaid::withDetail(WalkthroughStep::Searching, 'Searching indexers…', '3 indexers, 47 results'),
                ALineItSaid::withDetail(WalkthroughStep::Choosing, 'Selecting best match…', '1080p, matches your Balanced preset'),
                ALineItSaid::withDetail(WalkthroughStep::Grabbing, 'Sending to download client…', 'SABnzbd, via usenet'),
                ALineItSaid::withDetail(WalkthroughStep::Downloading, 'Downloading…', '2.1 GB · 14 MB/s · ~2m'),
                ALineItSaid::withDetail(WalkthroughStep::Importing, 'Importing…', 'copied to /data/media/movies'),
                ALineItSaid::withoutDetail(WalkthroughStep::Available, 'Available in Jellyfin'),
            ),
            inBackground: false,
            alreadyHere: false,
        )->handingOnTo(WhatComesNext::of(WhatToDoNext::MoreContent, WhatToDoNext::Household, WhatToDoNext::ClientApps))->linked(HowTheImportLinked::Copied);
    }

    /**
     * The body a stack answers the finished job with.
     *
     * @return array<string, mixed>
     */
    public static function whatAStackSaysOfTheWalkThatWorked(): array
    {
        return ['api_version' => 1, 'kind' => 'walkthrough', 'data' => self::theWalkThatWorkedAsAStackSendsIt()];
    }

    /**
     * What a stack sends under `data` for it.
     *
     * @return array<string, mixed>
     */
    public static function theWalkThatWorkedAsAStackSendsIt(): array
    {
        return [
            'shape' => 'pipeline',
            'state' => 'complete',
            'proves' => 'That every link from the indexers to the library works.',
            'item' => 'Big Buck Bunny',
            'lines' => [
                ['step' => 'searching', 'said' => 'Searching indexers…', 'detail' => '3 indexers, 47 results'],
                ['step' => 'choosing', 'said' => 'Selecting best match…', 'detail' => '1080p, matches your Balanced preset'],
                ['step' => 'grabbing', 'said' => 'Sending to download client…', 'detail' => 'SABnzbd, via usenet'],
                ['step' => 'downloading', 'said' => 'Downloading…', 'detail' => '2.1 GB · 14 MB/s · ~2m'],
                ['step' => 'importing', 'said' => 'Importing…', 'detail' => 'copied to /data/media/movies'],
                ['step' => 'available', 'said' => 'Available in Jellyfin', 'detail' => ''],
            ],
            'suggestions' => [],
            'in_background' => false,
            'already_here' => false,
            'link' => 'copied',
            'handover' => ['next' => ['more-content', 'household', 'client-apps']],
            'stopped' => null,
        ];
    }

    /** A walk that searched cleanly, matched nothing, and suggested two things instead. */
    public static function aWalkThatMatchedNothing(): AWalkthrough
    {
        return AWalkthrough::reported(
            WhichWalk::Pipeline,
            WhereTheWalkthroughIs::Failed,
            'That every link from the indexers to the library works.',
            WhatWasWalked::called('A Film Nobody Seeded'),
            TheLinesItSaid::of(
                ALineItSaid::withDetail(WalkthroughStep::Searching, 'Searching indexers…', '3 indexers, 0 results'),
            ),
            inBackground: false,
            alreadyHere: false,
        )->offering(WhatCouldBeWalkedInstead::of('Big Buck Bunny', 'Sintel'))->stoppedAt(WhereItStopped::at(
            WalkthroughStep::Searching,
            WhyTheWalkthroughStopped::NothingMatched,
            'Try one of the suggestions, which are well seeded.',
            WhatTheServicesWereSaying::of('prowlarr: query returned 0 results', ''),
        ));
    }

    /**
     * What a stack sends under `data` for it.
     *
     * @return array<string, mixed>
     */
    public static function theWalkThatMatchedNothingAsAStackSendsIt(): array
    {
        return [
            'shape' => 'pipeline',
            'state' => 'failed',
            'proves' => 'That every link from the indexers to the library works.',
            'item' => 'A Film Nobody Seeded',
            'lines' => [
                ['step' => 'searching', 'said' => 'Searching indexers…', 'detail' => '3 indexers, 0 results'],
            ],
            'suggestions' => ['Big Buck Bunny', 'Sintel'],
            'in_background' => false,
            'already_here' => false,
            'stopped' => [
                'step' => 'searching',
                'reason' => 'nothing-matched',
                'remedy' => 'Try one of the suggestions, which are well seeded.',
                'logs' => ['prowlarr: query returned 0 results', ''],
            ],
        ];
    }

    /** A walk asked for something the library already holds, left in the background. */
    public static function aWalkOfSomethingAlreadyHere(): AWalkthrough
    {
        return AWalkthrough::reported(
            WhichWalk::LibraryOnly,
            WhereTheWalkthroughIs::Complete,
            'That the media server can see what is on disk.',
            WhatWasWalked::called('Sintel'),
            TheLinesItSaid::of(
                ALineItSaid::withDetail(WalkthroughStep::Choosing, 'Checking it is not already here…', 'It is already in the library'),
            ),
            inBackground: true,
            alreadyHere: true,
        )->linked(HowTheImportLinked::Hardlinked);
    }

    /**
     * What a stack sends under `data` for it.
     *
     * @return array<string, mixed>
     */
    public static function theWalkOfSomethingAlreadyHereAsAStackSendsIt(): array
    {
        return [
            'shape' => 'library-only',
            'state' => 'complete',
            'proves' => 'That the media server can see what is on disk.',
            'item' => 'Sintel',
            'lines' => [
                ['step' => 'choosing', 'said' => 'Checking it is not already here…', 'detail' => 'It is already in the library'],
            ],
            'suggestions' => [],
            'in_background' => true,
            'already_here' => true,
            'link' => 'hardlinked',
            'handover' => ['next' => []],
        ];
    }
}
