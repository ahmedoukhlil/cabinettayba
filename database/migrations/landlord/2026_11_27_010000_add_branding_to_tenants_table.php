<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table): void {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('domain');
            $table->string('primary_color', 7)->default('#1e3a8a')->after('logo_path');
            $table->string('secondary_color', 7)->default('#0e7490')->after('primary_color');
            $table->string('reference_prefix', 12)->default('RDV')->after('secondary_color');
            $table->string('phone')->nullable()->after('reference_prefix');
            $table->string('email')->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'name_ar', 'logo_path', 'primary_color', 'secondary_color',
                'reference_prefix', 'phone', 'email', 'address',
            ]);
        });
    }
};
