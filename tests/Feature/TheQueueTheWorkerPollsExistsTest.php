<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Tree;

// The table the packager's queue worker reads exists.
//
// NativePHP starts a worker on every launch — a PHP runtime on its own thread
// calling `queue:work --once` in a loop — and forces `QUEUE_CONNECTION` to
// `database` from the platform layer, in `LaravelEnvironment.kt`. That is above
// `.env` and above `config/`: a value set in either is a value the device never
// consults, so *change the connection* is not a fix available here.
//
// This application dispatches nothing, so the worker polls an empty table
// forever, which is fine — as long as the table is there. It was not. Every
// poll missed, every miss was logged with a full stack trace, and
// `storage/logs/laravel.log` reached **55 MB** on a handset, where nothing
// rotates a log and no screen is any worse for it.
//
// **Nothing in this suite could have seen it, and that is the part worth
// keeping.** A suite runs no worker, so no test exercised the loop; phpunit.xml
// sets its own `QUEUE_CONNECTION`, so no test read the value that breaks; and
// the only symptom is a file on a device. It surfaced during an unrelated
// question about bundle size. What this rule buys is that the next time the
// migrations directory is emptied — a plausible tidy, since this application
// has no models — something says so before a phone does.

/**
 * The schema `migrate --force` produces, against a database nothing keeps.
 *
 * A named function rather than the calls at the site, because the analyser
 * refuses a checked exception escaping a closure and every Pest body is one.
 * In memory rather than at whatever file the environment points at, so this
 * creates nothing anybody has to clean up and cannot pass because a previous
 * run left the tables behind.
 *
 * @return array<string, bool> table => whether the migration made it
 */
function whatMigratingProduces(): array
{
    config(['database.connections.sqlite.database' => ':memory:']);
    DB::purge('sqlite');

    // Run rather than read off the directory, so a migration that exists and
    // does not work fails as readily as one that is missing. `migrate --force`
    // is what the device runs, and what it produces is what is asserted.
    //
    // Through the facade because `$this->artisan()` needs Mockery, which this
    // repository does not install.
    Artisan::call('migrate', ['--force' => true]);

    return ['jobs' => Schema::hasTable('jobs'), 'failed_jobs' => Schema::hasTable('failed_jobs')];
}

it('N1-R36 — the queue the packager polls has a table to poll', function (): void {
    $made = whatMigratingProduces();

    expect($made['jobs'])->toBeTrue(
        'The packager forces `QUEUE_CONNECTION=database` and polls `jobs` every few seconds. '
        . 'Without the table every poll is a logged stack trace, on a device, forever — it '
        . 'reached 55 MB before anything noticed. Add the queue migration.',
    );

    expect($made['failed_jobs'])->toBeTrue(
        'The framework\'s failed-job driver is `database-uuids`, so a job that fails writes '
        . 'to `failed_jobs`. A second missing table is this same defect with another name.',
    );
});

it('N1-R36 — the migration the device runs is in the tree', function (): void {
    // The floor for the rule above, and not the same question: the suite
    // migrates against its own connection, so a green `hasTable` proves the
    // schema works and not that anything ships it. This asks whether the device
    // has something to run.
    $found = Tree::filesUnder(Tree::at('database/migrations'), '.php');

    expect($found)->not->toBe(
        [],
        "There are no migrations, and the device runs `migrate --force` on every update.\n\n"
        . 'That command is how the packager expects an application to create the queue '
        . 'tables its worker reads. With nothing to run, the worker polls a table that '
        . "never appears.\n"
        . 'This application has no models and wants no schema of its own, which is exactly '
        . 'why an empty directory looks like a tidy rather than a regression (N1-R36).',
    );
});
