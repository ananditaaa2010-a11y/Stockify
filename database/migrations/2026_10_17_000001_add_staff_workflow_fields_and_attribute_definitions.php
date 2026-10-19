<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->string('staff_action')->nullable()->after('status');
            $table->timestamp('staff_action_at')->nullable()->after('staff_action');
        });

        Schema::create('product_attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('product_attributes', function (Blueprint $table) {
            $table->foreignId('definition_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_attribute_definitions')
                ->nullOnDelete();
        });

        DB::table('product_attributes')->orderBy('id')->get()->each(function ($attribute) {
            $definitionId = DB::table('product_attribute_definitions')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($attribute->name)])
                ->value('id');

            if ($definitionId === null) {
                $definitionId = DB::table('product_attribute_definitions')->insertGetId([
                    'name' => $attribute->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('product_attributes')->where('id', $attribute->id)->update([
                'definition_id' => $definitionId,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropForeign(['definition_id']);
            $table->dropColumn('definition_id');
        });

        Schema::dropIfExists('product_attribute_definitions');

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropColumn(['staff_action', 'staff_action_at']);
        });
    }
};
