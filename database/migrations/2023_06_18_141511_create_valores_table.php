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
        Schema::create('valores', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->string('page');
            $table->string('title');
            $table->string('subtitle');
            $table->string('informationTable');
            $table->string('name');
            $table->float('amount', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valores');
    }
};
