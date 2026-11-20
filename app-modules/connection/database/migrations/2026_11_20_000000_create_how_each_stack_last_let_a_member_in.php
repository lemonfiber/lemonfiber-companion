<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('connection_way_in_kept', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('noted_at');
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_way_in_kept');
    }
};
