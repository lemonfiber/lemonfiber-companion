<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Device\Api\SystemEntropy;
use Modules\Health\Api\KeepingTheLastReading;
use Modules\Health\Internal\Store\HealthReadingsInTheDatabase;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatStoppedMoving;
use Modules\Seal\Api\EncrypterSeal;
use Modules\Vault\Api\PlatformSealKeys;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsKeptForInMemory;

// What the phone keeps, read the way anybody holding the file would read it.
//
// Every store is handed only what `health` sealed, and the signatures say so
// (A12). This is the other half: the real seal and the real store over a
// database file, a summary kept through the capability that decides it, and
// then the file's own bytes searched for the summary's words and the stack's
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
        ReadingsKeptForInMemory::standard(),
        FrozenClock::at(Instant::atEpochSeconds(0)),
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
