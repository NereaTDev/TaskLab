<?php

use App\Models\CategoryType;
use App\Models\CategoryValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_type_id')->constrained('category_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->foreignId('parent_id')->nullable()->constrained('category_values')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed de ejemplo para "Áreas"
        $areasType = CategoryType::where('slug', Str::slug('Áreas'))->first();

        if ($areasType) {
            $ventas = CategoryValue::create([
                'category_type_id' => $areasType->id,
                'name'            => 'Ventas',
                'slug'            => Str::slug('Ventas'),
                'sort_order'      => 1,
            ]);

            CategoryValue::create([
                'category_type_id' => $areasType->id,
                'name'            => 'Ventas online',
                'slug'            => Str::slug('Ventas online'),
                'parent_id'       => $ventas->id,
                'sort_order'      => 1,
            ]);

            CategoryValue::create([
                'category_type_id' => $areasType->id,
                'name'            => 'Ventas telefónicas',
                'slug'            => Str::slug('Ventas telefónicas'),
                'parent_id'       => $ventas->id,
                'sort_order'      => 2,
            ]);

            CategoryValue::create([
                'category_type_id' => $areasType->id,
                'name'            => 'Producto',
                'slug'            => Str::slug('Producto'),
                'sort_order'      => 2,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_values');
    }
};
