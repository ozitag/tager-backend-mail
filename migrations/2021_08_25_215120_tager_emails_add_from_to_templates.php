<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TagerEmailsAddFromToTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tager_mail_templates', function (Blueprint $table) {
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tager_mail_templates', function (Blueprint $table) {
            $table->dropColumn('from_email');
            $table->dropColumn('from_name');
        });
    }
}
