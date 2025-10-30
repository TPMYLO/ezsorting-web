<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sorting_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('source_folder_id');
            $table->string('source_folder_name');
            $table->json('destination_folders')->nullable();
            $table->json('images')->nullable(); // Array of image file IDs and metadata
            $table->integer('total_images')->default(0);
            $table->integer('sorted_images')->default(0);
            $table->integer('remaining_images')->default(0);
            $table->integer('current_image_index')->default(0);
            $table->enum('status', ['setup', 'active', 'completed', 'paused'])->default('setup');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorting_sessions');
    }
};
