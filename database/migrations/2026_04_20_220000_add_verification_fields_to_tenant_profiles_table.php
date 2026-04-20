<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_profiles', function (Blueprint $table) {
            $table->string('contact_address', 255)->nullable()->after('contact_number');
            $table->enum('verification_status', ['Not_Submitted', 'Pending', 'Approved', 'Rejected'])
                ->default('Not_Submitted')
                ->after('emergency_contact');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->timestamp('verification_submitted_at')->nullable()->after('verification_note');
            $table->timestamp('verified_at')->nullable()->after('verification_submitted_at');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');

            $table->foreign('verified_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index('verification_status', 'tenant_profiles_verification_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_profiles', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex('tenant_profiles_verification_status_index');
            $table->dropColumn([
                'contact_address',
                'verification_status',
                'verification_note',
                'verification_submitted_at',
                'verified_at',
                'verified_by',
            ]);
        });
    }
};
