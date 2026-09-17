<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // หมวดของเลขเอกสาร - ตารางกลางที่ทุก template ใช้ร่วมกันได้
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // รหัสหมวดต้องไม่ซ้ำภายในบริษัทเดียวกัน
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->text('note')->nullable();
            // ตัวคั่นเริ่มต้น: อนุญาตเฉพาะ - และ _ ซ้ำได้ไม่เกิน 4 ตัว
            $table->string('separator', 4)->default('-');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('template_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            // เหลือแค่ 2 ชนิด: fixed = ข้อความคงที่, free = ผู้ใช้พิมพ์เอง
            $table->enum('type', ['fixed', 'free']);
            $table->unsignedInteger('position')->default(0);

            // ตัวคั่นหน้า segment นี้ - null = ใช้ค่าเริ่มต้นของ template
            $table->string('separator', 4)->nullable();

            // ใช้เมื่อ type = fixed
            $table->string('fixed_value', 40)->nullable();

            // ใช้เมื่อ type = free
            $table->unsignedTinyInteger('min_length')->nullable();
            $table->unsignedTinyInteger('max_length')->nullable();
            $table->enum('charset', ['A-Z0-9', '0-9', 'A-Z'])->nullable();

            $table->timestamps();

            $table->index(['template_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_segments');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('companies');
    }
};
