<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->string('visitor_photo_path')->nullable()->after('visitor_name');
        });

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->string('issue_photo_path')->nullable()->after('issue_desc');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropColumn('issue_photo_path');
        });

        Schema::table('visitor_logs', function (Blueprint $table) {
            $table->dropColumn('visitor_photo_path');
        });
    }
};
