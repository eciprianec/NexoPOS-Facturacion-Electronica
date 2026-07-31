<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nexopos_dgii_settings')) {
            Schema::create('nexopos_dgii_settings', function (Blueprint $table) {
                $table->id();
                $table->string('rnc_emisor', 20)->nullable();
                $table->string('razon_social', 255)->nullable();
                $table->string('nombre_comercial', 255)->nullable();
                $table->string('cert_path', 255)->nullable();
                $table->text('cert_password')->nullable();
                $table->string('environment', 20)->default('testecf');
                $table->boolean('auto_send_ecf')->default(true);
                $table->string('default_ncf_type_consumer', 10)->default('E32');
                $table->string('default_ncf_type_fiscal', 10)->default('E31');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('nexopos_dgii_sequences')) {
            Schema::create('nexopos_dgii_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('type_code', 10)->unique();
                $table->string('name', 255);
                $table->string('prefix', 10);
                $table->unsignedBigInteger('current_number')->default(1);
                $table->unsignedBigInteger('limit_number')->default(99999999);
                $table->date('expiration_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_ecf')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('nexopos_dgii_invoices')) {
            Schema::create('nexopos_dgii_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('ncf', 20)->index();
                $table->string('ecf_type', 10);
                $table->string('rnc_buyer', 20)->nullable();
                $table->string('buyer_name', 255)->nullable();
                $table->decimal('total_amount', 18, 5)->default(0);
                $table->decimal('tax_amount', 18, 5)->default(0);
                $table->string('track_id', 100)->nullable();
                $table->string('security_code', 100)->nullable();
                $table->string('status', 30)->default('pending');
                $table->text('response_message')->nullable();
                $table->string('xml_path', 255)->nullable();
                $table->string('signed_xml_path', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nexopos_dgii_invoices');
        Schema::dropIfExists('nexopos_dgii_sequences');
        Schema::dropIfExists('nexopos_dgii_settings');
    }
};
