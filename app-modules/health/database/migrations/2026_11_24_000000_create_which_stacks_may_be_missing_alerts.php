<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_alerts_may_be_missing', static function (Blueprint $table): void {
            $table->string('stack_hash')->primary();
            $table->unsignedBigInteger('noted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_alerts_may_be_missing');
    }
};
