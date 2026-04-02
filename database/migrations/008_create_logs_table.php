<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    public function up()
    {
        // Provision Logs
        Schema::create('provision_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->onDelete('cascade');
            $table->foreignId('onu_id')->nullable()->constrained('onus')->onDelete('set null');
            $table->string('action');
            $table->text('command');
            $table->text('response')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
        
        // ONU Traffic History
        Schema::create('onu_traffic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onu_id')->constrained('onus')->onDelete('cascade');
            $table->bigInteger('rx_bytes');
            $table->bigInteger('tx_bytes');
            $table->bigInteger('rx_packets');
            $table->bigInteger('tx_packets');
            $table->bigInteger('rx_errors')->default(0);
            $table->bigInteger('tx_errors')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['onu_id', 'recorded_at']);
        });
        
        // System Logs
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level')->default('info');
            $table->string('category');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('ip_address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('onu_traffic');
        Schema::dropIfExists('provision_logs');
    }
}
