<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The languages a member chose to hear and read titles in, one choice per stack, as the phone keeps it.
 *
 * One row per stack, found by the stack's keyed hash and never by its
 * identity. The choice, and whose it is, is a payload `watching` sealed
 * before this table saw it; beside it, readable, only the shape it was written
 * in and when it was chosen.
 *
 * Run by the `migrate --force` the platform runs itself. The migrator finds
 * this directory because the module registry hands it every module's
 * `database/migrations`.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watching_languages', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('read_at')->index();
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watching_languages');
    }
};
