<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("complaints", function (Blueprint $table) {
            $table->id();
            $table->foreignId("taxpayer_id")->nullable()->constrained("taxpayers")->onDelete("cascade");
            $table->string("name")->nullable();
            $table->string("email")->nullable();
            $table->string("phone")->nullable();
            $table->string("category");
            $table->text("complaint_text");
            $table->integer("rating")->nullable();
            $table->text("suggestion_text")->nullable();
            $table->json("attachments")->nullable();
            $table->enum("status", ["pending", "processing", "resolved", "rejected"])->default("pending");
            $table->text("admin_notes")->nullable();
            $table->timestamp("resolved_at")->nullable();
            $table->foreignId("resolved_by")->nullable()->constrained("users");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("complaints");
    }
};
