<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The newest health reading of each stack, as the phone keeps it between launches.
 *
 * One row per stack, found by the stack's keyed hash and never by its
 * identity. The reading itself is a payload `health` sealed before this table
 * saw it; beside it, readable, only what a query needs: the shape it was
 * written in, and when it was read, which is what finds the readings too old to
 * keep. The payload is bytes the table stores as it is handed them and never
 * converts, because nothing here can tell what they mean.
 *
 * Run by the `migrate --force` the platform runs itself, as the queue's tables
 * are. The migrator finds this directory because the module registry hands it
 * every module's `database/migrations`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_readings', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('read_at')->index();
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_readings');
    }
};
