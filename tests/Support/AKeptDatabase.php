<?php

declare(strict_types=1);

namespace Tests\Support;

use function config;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * The app's own database, in memory, for a suite that needs a real one.
 *
 * In memory rather than at whatever file the environment points at, so a run
 * creates nothing anybody has to clean up and cannot pass because an earlier
 * run left rows behind.
 */
final readonly class AKeptDatabase
{
    /** The connection the app uses, empty, with every migration a phone runs already run over it. */
    public static function migrated(): ConnectionInterface
    {
        $database = self::empty();

        // `migrate --force` is what the phone runs, so what it produces is
        // what a store is tested against — every module's migrations
        // included, found the way the phone finds them.
        Artisan::call('migrate', ['--force' => true]);

        return $database;
    }

    /** The connection the app uses, empty, with nothing migrated: a database whose tables are not there. */
    public static function empty(): ConnectionInterface
    {
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        return DB::connection('sqlite');
    }
}
