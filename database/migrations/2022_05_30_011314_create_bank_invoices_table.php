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
        Schema::create('bank_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('transmitter');
            $table->string('receiver');
            $table->float("amount");
            $table->longText("description");
            $table->enum('status', ['waiting', 'accepted', 'refuse'])->default('waiting');
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
        Schema::dropIfExists('bank_invoices');
    }
};
