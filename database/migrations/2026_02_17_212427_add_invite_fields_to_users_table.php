<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()

    {

        Schema::table('users', function (Blueprint $table) {

            $table->string('invite_status')->default('pending')->after('password');

            $table->timestamp('invite_sent_at')->nullable()->after('invite_status');

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down()

    {

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['invite_status', 'invite_sent_at']);

        });

    }
};
