<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the phone keeps about what is new on each stack, one row a stack: which
 * kinds are marked and the newest of each seen, sealed by `news` before it
 * arrives. A row names its stack only by a keyed hash.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('news_kept', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('noted_at');
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_kept');
    }
};
