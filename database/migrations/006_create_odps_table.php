<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOdpsTable extends Migration
{
    public function up()
    {
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->string('odp_name')->unique();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->integer('pon_port_id')->nullable();
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('total_ports')->default(16);
            $table->integer('used_ports')->default(0);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('odps');
    }
}
