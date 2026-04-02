<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOltsTable extends Migration
{
    public function up()
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('olt_name');
            $table->string('ip_address');
            $table->string('olt_model')->default('C300');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            
            // SNMP Configuration
            $table->string('snmp_community')->default('public');
            $table->string('snmp_read_community')->default('public');
            $table->string('snmp_write_community')->nullable();
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_version')->default('v2c');
            
            // Telnet Configuration
            $table->string('telnet_username');
            $table->text('telnet_password');
            $table->integer('telnet_port')->default(23);
            $table->integer('timeout')->default(10);
            
            // Map Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_poll')->nullable();
            $table->string('status')->default('unknown');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('olts');
    }
}
