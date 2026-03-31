<?php
namespace Modules\Log\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ScheduleLog extends Model
{
  use Prunable;

  protected $table = 'schedule_logs';

  protected $fillable = [
    'task_name',
    'command',
    'started_at',
    'finished_at',
    'exit_code',
    'output',
    'error',
    'duration',
    'triggered_by',
  ];

  protected $casts = [
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'exit_code' => 'integer',
    'duration' => 'float',
  ];

  /**
  * Get the prunable model query.
  *
  * @return \Illuminate\Database\Eloquent\Builder<static>
  */
  public function prunable() {
    $days = config("log.pruning.retention_days", 30);
    return static::where("created_at", "<", now()->subDays($days));

  }

  /**
  * Scope untuk log yang masih running (belum selesai)
  */
  public function scopeRunning($query) {
    return $query->whereNull('finished_at');
  }

  /**
  * Scope untuk log yang berhasil (exit_code = 0)
  */
  public function scopeSuccessful($query) {
    return $query->where('exit_code', 0);
  }

  /**
  * Scope untuk log yang gagal (exit_code != 0)
  */
  public function scopeFailed($query) {
    return $query->where('exit_code', '!=', 0)->whereNotNull('exit_code');
  }

  /**
  * Scope untuk filter berdasarkan nama task
  */
  public function scopeForTask($query, $taskName) {
    return $query->where('task_name', $taskName);
  }

  /**
  * Scope untuk log dalam beberapa hari terakhir
  */
  public function scopeLastDays($query, $days = 30) {
    return $query->where('created_at', '>=', now()->subDays($days));
  }

  /**
  * Accessor: apakah log ini berhasil?
  */
  public function getIsSuccessfulAttribute() {
    return $this->exit_code === 0;
  }

  /**
  * Accessor: apakah log ini masih running?
  */
  public function getIsRunningAttribute() {
    return is_null($this->finished_at);
  }

  /**
  * Mengambil definisi task dari konfigurasi (opsional)
  */
  public function taskDefinition() {
    $tasks = config('schedule-monitor.tasks', []);
    foreach ($tasks as $task) {
      if ($task['name'] === $this->task_name) {
        return (object) $task;
      }
    }
    return null;
  }
}