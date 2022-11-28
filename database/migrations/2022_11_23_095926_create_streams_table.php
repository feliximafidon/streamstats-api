<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('streams', function (Blueprint $table) {
            //id, user_id, user_login, user_name, game_id, game_name, type, title, viewer_count, started_at, language, thumbnail_url, tag_ids, is_mature
            $table->id('auto_id');
            $table->unsignedBigInteger('id')->index();
            $table->unsignedBigInteger('user_id');
            $table->string('user_name');
            $table->string('game_id', 30)->index();
            $table->string('game_name');
            $table->string('type', 20);
            $table->text('title');
            $table->unsignedInteger('viewer_count');
            $table->text('thumbnail_url');

            $table->boolean('is_archived')->default(false)->index(); // Index is important. We will be reading true records mostly, but there will be more false records
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('streams');
    }
};
