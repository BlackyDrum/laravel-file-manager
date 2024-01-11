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
        Schema::create('shared_file_has_privilege', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shared_file_id');
            $table->unsignedBigInteger('privilege_id');
            $table->timestamps();

            $table->foreign('shared_file_id')->references('id')->on('shared_files')->onDelete('cascade');
            $table->foreign('privilege_id')->references('id')->on('shared_files_privileges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_file_has_privilege');
    }
};
