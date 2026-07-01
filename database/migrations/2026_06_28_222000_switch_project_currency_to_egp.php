<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'orders', 'payments', 'bank_transfers'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('currency', ['SAR', 'USD'])->update(['currency' => 'EGP']);
            }
        }
    }

    public function down(): void
    {
        foreach (['products', 'orders', 'payments', 'bank_transfers'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('currency', 'EGP')->update(['currency' => 'SAR']);
            }
        }
    }
};
