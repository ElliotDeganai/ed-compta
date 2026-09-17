<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_items', function (Blueprint $table) {
            // Decale la ligne au jour ouvre suivant. Coche pour un salaire :
            // le versement n'a pas lieu un jour non ouvre. Decoche pour un
            // prelevement dont la date est imposee par le creancier.
            $table->boolean('shift_to_business_day')->default(false)->after('day_of_month');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_items', function (Blueprint $table) {
            $table->dropColumn('shift_to_business_day');
        });
    }
};
