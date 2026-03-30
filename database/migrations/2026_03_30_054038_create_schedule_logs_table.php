<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('schedule_logs', function (Blueprint $table) {
      $table->id();
      $table->string('task_name');
      $table->string('command');
      $table->dateTime('started_at')->nullable();
      $table->dateTime('finished_at')->nullable();
      $table->integer('exit_code')->nullable();
      $table->longText('output')->nullable();
      $table->longText('error')->nullable();
      $table->float('duration')->nullable(); // dalam detik
      $table->string('triggered_by')->nullable(); // 'schedule' atau 'manual'
      $table->timestamps();

      $table->index('task_name');
      $table->index('started_at');
    });
  }

  public function down() {
    Schema::dropIfExists('schedule_logs');
  }
};