<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->char('target_key', 64)->unique();
            $table->char('endpoint_hash', 64)->index();
            $table->text('endpoint');
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('public_appointment_request_id')->nullable()->index('push_subscription_request_index');
            $table->unsignedInteger('cabinet_id')->index();
            $table->string('target_url', 2048);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
