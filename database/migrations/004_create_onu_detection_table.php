<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnuDetectionTable extends Migration
{
    public function up()
    {
        Schema::create('onu_detection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->integer('slot');
            $table->integer('pon_port');
            $table->string('onu_sn');
            $table->string('onu_password')->nullable();
            $table->string('onu_type')->nullable();
            $table->string('loid')->nullable();
            $table->string('loid_password')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('hardware_version')->nullable();
            $table->timestamp('discovery_time');
            $table->string('status')->default('detected');
            $table->boolean('is_ignored')->default(false);
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
            
            $table->unique(['olt_id', 'slot', 'pon_port', 'onu_sn']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('onu_detection');
    }
}
