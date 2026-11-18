<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('public_appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fkidcabinet')->index();
            $table->unsignedInteger('fkidmedecin')->index();
            $table->unsignedInteger('fkidacte')->nullable();
            $table->string('nom', 120);
            $table->string('prenom', 120)->nullable();
            $table->string('telephone', 30);
            $table->date('date_souhaitee');
            $table->time('heure_souhaitee');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('reference', 32)->unique();
            $table->string('tracking_token_hash', 64);
            $table->string('slot_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedInteger('patient_id')->nullable();
            $table->unsignedInteger('appointment_id')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(
                ['fkidcabinet', 'fkidmedecin', 'date_souhaitee', 'heure_souhaitee'],
                'public_appointment_slot_index'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('public_appointment_requests');
    }
};
