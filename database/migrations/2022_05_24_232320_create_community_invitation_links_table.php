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
        Schema::create('community_invitation_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('code');
            $table->integer('nb_invitations')->default(0);
            $table->integer('invitations_limit')->default(100);
            $table->timestamp("expiration_date", $precision = 0)->nullable();
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
        Schema::dropIfExists('community_invitation_links');
    }
};
