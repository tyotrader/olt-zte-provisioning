<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePonPortsTable extends Migration
{
    public function up()
    {
        Schema::create('pon_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->integer('slot');
            $table->integer('port');
            $table->string('pon_type')->default('GPON');
            $table->integer('max_onu')->default(128);
            $table->integer('current_onu_count')->default(0);
            $table->integer('online_onu')->default(0);
            $table->integer('offline_onu')->default(0);
            $table->decimal('average_rx_power', 8, 2)->nullable();
            $table->string('admin_status')->default('up');
            $table->string('oper_status')->default('up');
            $table->decimal('utilization', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['olt_id', 'slot', 'port']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pon_ports');
    }
}
