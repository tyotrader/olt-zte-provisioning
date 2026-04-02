<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceProfilesTable extends Migration
{
    public function up()
    {
        // TCONT Profiles
        Schema::create('tcont_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_name')->unique();
            $table->integer('tcont_id');
            $table->string('bandwidth_profile');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Bandwidth Profiles
        Schema::create('bandwidth_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_name')->unique();
            $table->string('profile_type')->default('fixed');
            $table->integer('fixed_bw')->default(0);
            $table->integer('assure_bw')->default(0);
            $table->integer('max_bw');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // GEM Port Templates
        Schema::create('gemport_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->unique();
            $table->integer('gemport_id');
            $table->string('tcont_profile');
            $table->string('traffic_class')->default('be');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Service Port Templates
        Schema::create('service_port_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name')->unique();
            $table->integer('service_port_id');
            $table->integer('vport')->default(1);
            $table->integer('user_vlan');
            $table->integer('c_vid')->nullable();
            $table->string('vlan_mode')->default('tag');
            $table->string('translation_mode')->default('vlan-stacking');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // VLAN Profiles
        Schema::create('vlan_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_name')->unique();
            $table->integer('vlan_id');
            $table->string('vlan_name')->nullable();
            $table->string('vlan_type')->default('residential');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vlan_profiles');
        Schema::dropIfExists('service_port_templates');
        Schema::dropIfExists('gemport_templates');
        Schema::dropIfExists('bandwidth_profiles');
        Schema::dropIfExists('tcont_profiles');
    }
}
