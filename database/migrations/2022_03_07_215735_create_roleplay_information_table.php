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
        Schema::create('roleplay_information', function (Blueprint $table) {
            $table->uuid();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birthday');
            $table->string('address');
            $table->string('city');
            $table->string('country');
            $table->string('zip_code');
            $table->string('phone_number');
            $table->integer('height')->max(250)->default(170);
            $table->integer('weight')->max(500)->default(60);
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
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
        Schema::dropIfExists('roleplay_information');
    }
};
