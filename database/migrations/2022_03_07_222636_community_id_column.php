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
            $table->string('name')
                ->unique();
            $table->string('email')->unique();
            $table->foreignId('community_id')
                ->constrained()
                ->nullable();
            $table->enum('community_role', ['admin', 'moderator', 'member'])->default('member');
            $table->timestamp('email_verified_at')->nullable();
            $table->ipAddress('visitor');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
