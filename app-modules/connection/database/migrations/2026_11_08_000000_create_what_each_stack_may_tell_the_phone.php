<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the operator lets reach the phone from each stack, one row a stack: the
 * kinds of notification switched off and the lowest severity of problem told,
 * sealed by `connection` before it arrives. A row names its stack only by a
 * keyed hash.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('connection_notifying_kept', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('noted_at');
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_notifying_kept');
    }
};
