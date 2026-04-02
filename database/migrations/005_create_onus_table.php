<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnusTable extends Migration
{
    public function up()
    {
        Schema::create('onus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->foreignId('pon_port_id')->constrained('pon_ports')->onDelete('cascade');
            $table->foreignId('odp_id')->nullable()->constrained('odps')->onDelete('set null');
            
            // ONU Identification
            $table->integer('onu_id');
            $table->string('onu_sn')->unique();
            $table->string('onu_type')->default('F601');
            $table->string('customer_name');
            $table->string('customer_id')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            
            // PON Location
            $table->integer('slot');
            $table->integer('pon_port');
            
            // Configuration
            $table->string('tcont_profile')->nullable();
            $table->string('gemport_template')->nullable();
            $table->string('vlan_profile')->nullable();
            $table->string('service_port_template')->nullable();
            
            // WAN Configuration
            $table->string('wan_mode')->default('pppoe');
            $table->string('pppoe_username')->nullable();
            $table->string('pppoe_password')->nullable();
            $table->string('static_ip')->nullable();
            $table->string('static_gateway')->nullable();
            $table->string('static_subnet')->nullable();
            
            // WiFi Configuration
            $table->string('wifi_ssid')->nullable();
            $table->string('wifi_password')->nullable();
            $table->boolean('wifi_enabled')->default(true);
            
            // Optical Data
            $table->decimal('rx_power', 8, 2)->nullable();
            $table->decimal('tx_power', 8, 2)->nullable();
            $table->decimal('distance', 8, 2)->nullable();
            $table->decimal('temperature', 6, 2)->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('uptime')->nullable();
            
            // Status
            $table->string('status')->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('registered_at');
            
            // Map Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['olt_id', 'slot', 'pon_port', 'onu_id']);
            $table->index(['status', 'last_seen']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('onus');
    }
}
