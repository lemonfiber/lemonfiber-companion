<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether each stack wakes the phone through a distributor, one row a stack:
 * the distributor, what the stack was asked to keep, or a removal still owed
 * to it, sealed by `connection` before it arrives. A row names its stack only
 * by a keyed hash.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('connection_waking_kept', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('noted_at');
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_waking_kept');
    }
};
