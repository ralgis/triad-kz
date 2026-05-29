<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the freeform `dimensions` JSON column with 11 typed
 * spec columns so the catalog can filter / sort / compare on real
 * fields instead of string substrings inside descriptions.
 *
 * Inventory comes from auditing all 38 legacy products (see
 * GostsSeeder discussion — the same script): 12 unique parameters
 * total, of which 3 are universal (volume / grade / weight), 8 are
 * geometric (length, width, height, thickness + four diameter
 * variants), and 1 is reinforcement (steel kg). `weight_kg` already
 * exists on products from the initial schema; we keep that column
 * and convert legacy «тн» values to kg during import.
 *
 * `dimensions` JSON column kept transitionally — dropped in a
 * follow-up migration once the prod re-extract is verified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Geometry — all stored as integer mm because legacy data
            // is uniformly in whole millimetres, no sub-mm precision.
            $table->unsignedInteger('length_mm')->nullable()->after('gost');
            $table->unsignedInteger('width_mm')->nullable()->after('length_mm');
            $table->unsignedInteger('height_mm')->nullable()->after('width_mm');
            $table->unsignedInteger('thickness_mm')->nullable()->after('height_mm');

            $table->unsignedInteger('inner_diameter_mm')->nullable()->after('thickness_mm');
            $table->unsignedInteger('outer_diameter_mm')->nullable()->after('inner_diameter_mm');
            $table->unsignedInteger('plate_diameter_mm')->nullable()->after('outer_diameter_mm');
            $table->unsignedInteger('hole_diameter_mm')->nullable()->after('plate_diameter_mm');

            // Material — concrete grade is a short string (M200/M300/
            // M350 today) kept as varchar rather than ENUM so new
            // grades land without a migration.
            $table->string('concrete_grade', 8)->nullable()->after('hole_diameter_mm');

            // Volume up to 99.999 м³ covers anything we'd produce; 3
            // decimals because legacy values go down to 0.005 м³.
            $table->decimal('concrete_volume_m3', 6, 3)->nullable()->after('concrete_grade');

            // Steel consumption per unit, kilos.
            $table->decimal('steel_kg', 6, 2)->nullable()->after('concrete_volume_m3');

            // Welded-mesh-specific parameters. Catalog has the
            // category but no products in legacy — keeping the schema
            // ready so when «Сетка сварная» items get added (or import
            // from an external supplier list), there's no extra
            // migration round-trip. Cells stored as two ints because
            // welded mesh can be square (100×100) or rectangular
            // (50×100); querying «cell width ≤ 50» is then trivial.
            $table->unsignedInteger('mesh_rod_diameter_mm')->nullable()->after('steel_kg');
            $table->unsignedInteger('mesh_cell_length_mm')->nullable()->after('mesh_rod_diameter_mm');
            $table->unsignedInteger('mesh_cell_width_mm')->nullable()->after('mesh_cell_length_mm');

            // Indexes on the parameters likely to drive catalog filters
            // (length / height / weight / grade). Diameters / thickness
            // can index later if filter UX needs them.
            $table->index('length_mm');
            $table->index('height_mm');
            $table->index('concrete_grade');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['length_mm']);
            $table->dropIndex(['height_mm']);
            $table->dropIndex(['concrete_grade']);

            $table->dropColumn([
                'length_mm',
                'width_mm',
                'height_mm',
                'thickness_mm',
                'inner_diameter_mm',
                'outer_diameter_mm',
                'plate_diameter_mm',
                'hole_diameter_mm',
                'concrete_grade',
                'concrete_volume_m3',
                'steel_kg',
                'mesh_rod_diameter_mm',
                'mesh_cell_length_mm',
                'mesh_cell_width_mm',
            ]);
        });
    }
};
