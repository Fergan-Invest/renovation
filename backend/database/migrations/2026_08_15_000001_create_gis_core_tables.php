<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('kml');
            $table->string('source_file')->nullable();
            $table->json('style')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('excel_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('status')->default('pending');
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('excel_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('excel_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('match_key')->nullable()->index();
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('map_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('layer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('excel_row_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable()->index();
            $table->string('external_id')->nullable()->index();
            $table->string('cadastre_number')->nullable()->index();
            $table->string('geometry_type')->nullable();
            $table->geometry('geometry')->nullable();
            $table->geometry('geometry_simplified')->nullable();
            $table->json('properties')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE map_features ADD SPATIAL INDEX map_features_geometry_spatial (geometry)');
        DB::statement('ALTER TABLE map_features ADD SPATIAL INDEX map_features_geometry_simplified_spatial (geometry_simplified)');
    }

    public function down(): void
    {
        Schema::dropIfExists('map_features');
        Schema::dropIfExists('excel_rows');
        Schema::dropIfExists('excel_imports');
        Schema::dropIfExists('layers');
        Schema::dropIfExists('projects');
    }
};

