<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('text_to_images', function (Blueprint $table) {
            $table->dropColumn('raw_response');
        });
    }

    public function down()
    {
        Schema::table('text_to_images', function (Blueprint $table) {
            $table->json('raw_response')->nullable();
        });
    }
}; 