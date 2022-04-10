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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->float('balance', 12, 2)->default(0);
            $table->string('name');
            $table->uuid('rib')
                ->unique();
            $table->enum('type', ['personnal', 'professional'])->default('personnal');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('community_id')
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
        Schema::dropIfExists('bank_accounts');
    }
};
