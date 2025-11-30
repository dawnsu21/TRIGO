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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete(); // Who gave the feedback
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete(); // Who received the feedback
            $table->enum('from_role', ['passenger', 'driver']); // Role of person giving feedback
            $table->integer('rating')->default(1)->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->unique(['ride_id', 'from_user_id']); // One feedback per user per ride
            $table->index(['to_user_id', 'from_role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};

