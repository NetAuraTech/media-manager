<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_alts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')
                ->constrained('medias')
                ->onDelete('cascade');
            $table->text('alt_text');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['media_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_alts');
    }
};