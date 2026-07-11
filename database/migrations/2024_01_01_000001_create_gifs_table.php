<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gifs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('keywords', 500)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('imgbb_url', 500);
            $table->string('imgbb_delete_url', 500)->nullable();
            $table->string('proxy_path', 100)->unique();
            $table->unsignedInteger('file_size')->default(0);
            $table->string('mime_type', 50)->default('image/gif');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index('keywords');
            $table->index('proxy_path');
            $table->index('created_at');
            $table->index('views');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gifs');
    }
};
