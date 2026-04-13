<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_products', 'features')) {
                $table->text('features')->nullable()->after('description');
            }
            if (! Schema::hasColumn('shop_products', 'image')) {
                $table->string('image')->nullable()->after('features');
            }
            if (! Schema::hasColumn('shop_products', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('shop_products', 'gallery_images')) {
                $drop[] = 'gallery_images';
            }
            if (Schema::hasColumn('shop_products', 'image')) {
                $drop[] = 'image';
            }
            if (Schema::hasColumn('shop_products', 'features')) {
                $drop[] = 'features';
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
