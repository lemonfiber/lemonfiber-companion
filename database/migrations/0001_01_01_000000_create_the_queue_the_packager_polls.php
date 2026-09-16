<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tables the packager's queue worker reads, which this application does not
 * otherwise need.
 *
 * Nothing here dispatches a job. This exists because NativePHP starts a queue
 * worker on every launch regardless — a PHP runtime on its own thread calling
 * `queue:work --once` in a loop — and forces the connection to `database` from
 * the platform layer, in `LaravelEnvironment.kt`, where no config file or `.env`
 * of ours can reach it.
 *
 * So the worker polls a table, and this application had none. What that produced
 * on a handset is the reason this file exists: every poll missed, every miss was
 * logged with a full stack trace, and `storage/logs/laravel.log` reached **55 MB**
 * on somebody's phone — where nothing rotates a log and no screen is any worse
 * for it. Nothing in the suite could see it, because a suite runs no worker.
 *
 * **The alternative was fighting the platform and it is worse.** A config file
 * saying `sync` looks like it governs and does not; patching the Kotlin the
 * packager regenerates each build is a patch with no home. An empty table the
 * worker finds empty costs a few kilobytes and makes the loop correct.
 *
 * `failed_jobs` beside it because the framework's failed-job driver is
 * `database-uuids`: a job that ever does fail writes there, and a second missing
 * table would be this same bug with a different name.
 *
 * Laravel's own stubs, unchanged, so a reader recognises them and nothing here
 * has an opinion about a schema it does not use.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            $table->index(['connection', 'queue', 'failed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
};
