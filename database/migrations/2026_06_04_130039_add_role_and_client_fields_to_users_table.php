<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->after('email');
            $table->string('client_type')->nullable()->after('role');
            $table->foreignId('parent_company_id')->nullable()->constrained('users')->nullOnDelete()->after('client_type');
            $table->string('phone')->nullable()->after('parent_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_company_id']);
            $table->dropColumn(['role', 'client_type', 'parent_company_id', 'phone']);
        });
    }
};
