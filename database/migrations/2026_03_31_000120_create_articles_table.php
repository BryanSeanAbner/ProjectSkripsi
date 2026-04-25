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
        Schema::create('articles', function (Blueprint $table) {
            $table->unsignedBigInteger('article_id')->primary();
            $table->text('title');
            $table->longText('content');
            $table->text('photo_url')->nullable();
            $table->dateTime('publish_date')->nullable();
            $table->text('url')->nullable();
            $table->string('section_id', 50)->index();
            $table->unsignedBigInteger('view_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
