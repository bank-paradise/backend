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
        Schema::create('bank_salary_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')
                ->constrained()
                ->onDelete('cascade');
            $table->float("salary");
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
        Schema::dropIfExists('bank_salary_requests');
    }
};
