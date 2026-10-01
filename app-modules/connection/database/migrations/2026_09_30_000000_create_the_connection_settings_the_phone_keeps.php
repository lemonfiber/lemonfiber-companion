<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One row: this module's settings for the whole phone, sealed as one
        // value, in the shape it was written in and with when it was set.
        Schema::create('connection_settings', static function (Blueprint $table): void {
            $table->unsignedTinyInteger('row')->primary();
            $table->unsignedInteger('shape');
            $table->unsignedBigInteger('set_at');
            $table->binary('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_settings');
    }
};
