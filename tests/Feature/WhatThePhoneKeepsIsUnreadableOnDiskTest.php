<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Device\Api\SystemEntropy;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AReleaseNamed;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Kernel\Api\WhatTheStackListed;
use Modules\News\Api\Noticing;
use Modules\News\Internal\NewsOfAStack;
use Modules\News\Internal\Store\NewsInTheDatabase;
use Modules\News\Internal\WhatEachStackLastNamed;
use Modules\Seal\Api\EncrypterSeal;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Services\Internal\Store\ListingsInTheDatabase;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Updates\Internal\Store\UpkeepReadingsInTheDatabase;
use Modules\Vault\Api\PlatformSealKeys;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\FrozenClock;

// What the phone keeps, read the way anybody holding the file would read it.
//
// Every store is handed only what its owner sealed, and the signatures say so
// (A12). This is the other half: the real seal and the real store over a
// database file, a reading kept through the capability that decides it, and
// then the file's own bytes searched for the reading's words and the stack's
// identity. On Android that file sits beside the framework's own key, so what
// it must not hold is anything that key, or no key at all, could read.

/** The stack whose summary is kept, as it names itself. */
const THE_STACK_WHOSE_HEALTH_IS_KEPT = 'c3d4e5f60718293a';

/** A sentence only the kept summary holds, so finding it in the file finds the summary. */
const WHAT_THE_KEPT_SUMMARY_SAYS = 'The disk that holds the photos of the loft is full';

/** The app's own database, as a file under the system's temporary directory, with every migration a phone runs run over it. */
function aDatabaseFileMigrated(string $file): ConnectionInterface
{
    config(['database.connections.sqlite.database' => $file]);
    DB::purge('sqlite');
    Artisan::call('migrate', ['--force' => true]);

    return DB::connection('sqlite');
}

/** Keep one summary through the capability that decides it, over the real seal and the real store, and say whether it was written. */
function keepTheSummaryIn(ConnectionInterface $database): string
{
    $keeping = new KeepingTheLastReading(
        new EncrypterSeal(new PlatformSealKeys(APlatformStore::working()), new SystemEntropy()),
        new HealthReadingsInTheDatabase($database),
    );

    return $keeping->keep(
        StackId::of(Nonce::of(THE_STACK_WHOSE_HEALTH_IS_KEPT)),
        TheHealthSummary::of(HowItStands::Broken, 1, WHAT_THE_KEPT_SUMMARY_SAYS, WhatStoppedMoving::nothing()),
        Instant::atEpochSeconds(1_790_000_000),
    )->either(
        down: static fn(): Code => Code::of('written'),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('writes a kept summary to the database file with nothing of it, or of its stack, left readable', function (): void {
    $file = (string) tempnam(sys_get_temp_dir(), 'kept');
    $written = keepTheSummaryIn(aDatabaseFileMigrated($file));
    DB::disconnect('sqlite');
    $onDisk = (string) file_get_contents($file);
    unlink($file);

    // The floor: a summary that was never written, or a file that is not the
    // database it went to, would find no trace of anything.
    expect($written)->toBe('written')
        ->and($onDisk)->toContain('health_readings')
        ->and($onDisk)->not->toContain(WHAT_THE_KEPT_SUMMARY_SAYS)
        ->and($onDisk)->not->toContain('photos of the loft')
        ->and($onDisk)->not->toContain(THE_STACK_WHOSE_HEALTH_IS_KEPT);
});

/** A service only the kept reading of where the stack stands names, so finding it in the file finds the reading. */
const THE_SERVICE_ONLY_THE_KEPT_UPKEEP_NAMES = 'photoprism-of-the-loft';

/** Keep one reading of where the stack stands through the capability that decides it, over the real seal and the real store, and say whether it was written. */
function keepTheUpkeepIn(ConnectionInterface $database): string
{
    $keeping = new KeepingTheLastUpkeep(
        new EncrypterSeal(new PlatformSealKeys(APlatformStore::working()), new SystemEntropy()),
        new UpkeepReadingsInTheDatabase($database),
    );

    return $keeping->keep(
        StackId::of(Nonce::of(THE_STACK_WHOSE_HEALTH_IS_KEPT)),
        Upkeep::reported(
            AgainstThePins::UpdatesAvailable,
            Releases::none(),
            Services::these(ServiceId::called(THE_SERVICE_ONLY_THE_KEPT_UPKEEP_NAMES)),
            Services::none(),
            HowServicesTookIt::none(),
            HowTheNotesStand::Current,
            TheStackEdits::none(),
        ),
        Instant::atEpochSeconds(1_790_000_000),
    )->either(
        down: static fn(): Code => Code::of('written'),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('writes a kept reading of where a stack stands to the database file with nothing of it, or of its stack, left readable', function (): void {
    $file = (string) tempnam(sys_get_temp_dir(), 'kept');
    $written = keepTheUpkeepIn(aDatabaseFileMigrated($file));
    DB::disconnect('sqlite');
    $onDisk = (string) file_get_contents($file);
    unlink($file);

    expect($written)->toBe('written')
        ->and($onDisk)->toContain('updates_readings')
        ->and($onDisk)->not->toContain(THE_SERVICE_ONLY_THE_KEPT_UPKEEP_NAMES)
        ->and($onDisk)->not->toContain('updates-available')
        ->and($onDisk)->not->toContain(THE_STACK_WHOSE_HEALTH_IS_KEPT);
});

/** A service only the kept listing of what the stack runs names, so finding it in the file finds the listing. */
const THE_SERVICE_ONLY_THE_KEPT_LISTING_NAMES = 'nextcloud-of-the-loft';

/** Keep one listing of what the stack runs through the capability that decides it, over the real seal and the real store, and say whether it was written. */
function keepTheListingIn(ConnectionInterface $database): string
{
    $keeping = new KeepingWhatItRuns(
        new EncrypterSeal(new PlatformSealKeys(APlatformStore::working()), new SystemEntropy()),
        new ListingsInTheDatabase($database),
        FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)),
    );

    return $keeping->keep(
        StackId::of(Nonce::of(THE_STACK_WHOSE_HEALTH_IS_KEPT)),
        Daemons::of(
            HowTheStackIsRunning::Active,
            Disturbances::of(WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1)),
            Daemon::called('Nextcloud', ServiceId::called(THE_SERVICE_ONLY_THE_KEPT_LISTING_NAMES), HowAServiceRuns::Running, HowMuchItMatters::Core, WhatLeansOnIt::nothing()),
        ),
    )->either(
        down: static fn(): Code => Code::of('written'),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

it('writes a kept listing of what a stack runs to the database file with nothing of it, or of its stack, left readable', function (): void {
    $file = (string) tempnam(sys_get_temp_dir(), 'kept');
    $written = keepTheListingIn(aDatabaseFileMigrated($file));
    DB::disconnect('sqlite');
    $onDisk = (string) file_get_contents($file);
    unlink($file);

    expect($written)->toBe('written')
        ->and($onDisk)->toContain('services_readings')
        ->and($onDisk)->not->toContain(THE_SERVICE_ONLY_THE_KEPT_LISTING_NAMES)
        ->and($onDisk)->not->toContain('Nextcloud')
        ->and($onDisk)->not->toContain(THE_STACK_WHOSE_HEALTH_IS_KEPT);
});

/** A release only the newest the stack names after its first reading holds, so finding it in the file finds what was heard. */
const THE_RELEASE_ONLY_HEARD = '9.8.7-heard-and-never-kept';

/** What a stack names as newest: these releases, and no request or problem. */
function theNewestThatNamesReleases(string ...$releases): TheNewestNamed
{
    return new TheNewestNamed(
        WhatTheStackListed::these(...array_map(AReleaseNamed::versioned(...), $releases)),
        WhatTheStackListed::these(),
        WhatTheStackListed::these(),
    );
}

it('holds what a stack names as newest, and how much of it is new, in memory and writes none of it to the database file', function (): void {
    $file = (string) tempnam(sys_get_temp_dir(), 'kept');
    $database = aDatabaseFileMigrated($file);
    $noticing = new Noticing(new NewsOfAStack(
        new EncrypterSeal(new PlatformSealKeys(APlatformStore::working()), new SystemEntropy()),
        new NewsInTheDatabase($database),
        FrozenClock::at(Instant::atEpochSeconds(1_790_000_000)),
        new WhatEachStackLastNamed(),
    ));
    $stack = StackId::of(Nonce::of(THE_STACK_WHOSE_HEALTH_IS_KEPT));

    $noticing->howMuchIsNew($stack, theNewestThatNamesReleases('2.3.0'));
    $keptAtTheFirstReading = $database->table('news_kept')->get()->toJson();
    $heard = $noticing->howMuchIsNew($stack, theNewestThatNamesReleases(THE_RELEASE_ONLY_HEARD, '2.3.0'))->howManyInAll();
    $keptOnceHeard = $database->table('news_kept')->get()->toJson();
    DB::disconnect('sqlite');
    $onDisk = (string) file_get_contents($file);
    unlink($file);

    // The floor: the first reading records what is current as seen, so the
    // file holds a row, and the second names one release that is new.
    expect($heard)->toBe(1)
        ->and($noticing->howMuchWasLastNamed($stack)->howManyInAll())->toBe(1)
        ->and($onDisk)->toContain('news_kept')
        ->and($keptOnceHeard)->toBe($keptAtTheFirstReading)
        ->and($onDisk)->not->toContain(THE_RELEASE_ONLY_HEARD)
        ->and($onDisk)->not->toContain(THE_STACK_WHOSE_HEALTH_IS_KEPT);
});
