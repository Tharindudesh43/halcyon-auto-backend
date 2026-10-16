<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_listings', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        $used = [];
        foreach (DB::table('car_listings')->orderBy('id')->get(['id', 'brand', 'model', 'year', 'title']) as $car) {
            $base = Str::slug("{$car->brand} {$car->model} {$car->year}") ?: Str::slug($car->title) ?: 'car';
            $slug = $base;
            for ($i = 2; isset($used[$slug]); $i++) {
                $slug = "{$base}-{$i}";
            }
            $used[$slug] = true;
            DB::table('car_listings')->where('id', $car->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('car_listings', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
