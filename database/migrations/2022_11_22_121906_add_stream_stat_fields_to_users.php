<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('users', function (Blueprint $table) {
            $table->after('id', function(Blueprint $table) {
                $table->unsignedBigInteger('twitch_id')->unique();
                $table->string('username', 80)->unique();
            });

            $table->after('remember_token', function(Blueprint $table) {
                // $table->string('access_token'); // Use cache instead
                $table->text('avatar');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'twitch_id',
                'username',
                // 'access_token',
                'avatar',
            ]);
        });
    }
};
