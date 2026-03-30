<?php

namespace Modules\Log\Listeners;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Modules\Log\Models\ScheduleLog;

class LogScheduledTask
{
  protected $startTimes = [];

  public function handle($event) {
    if ($event instanceof ScheduledTaskStarting) {
      $this->handleStarting($event->task);
    } elseif ($event instanceof ScheduledTaskFinished) {
      $this->handleFinished($event->task);
    }
  }

  protected function handleStarting(ScheduledEvent $task) {
    $taskId = $this->getTaskId($task);
    $this->startTimes[$taskId] = microtime(true);

    ScheduleLog::create([
      'task_name' => $this->getTaskName($task),
      'command' => $this->getCommandString($task),
      'started_at' => now(),
      'triggered_by' => 'schedule',
    ]);
  }

  protected function handleFinished(ScheduledEvent $task) {
    $taskId = $this->getTaskId($task);
    $startTime = $this->startTimes[$taskId] ?? null;
    $duration = $startTime ? round(microtime(true) - $startTime, 2) : null;

    // Cari log terakhir yang belum selesai untuk task ini
    $log = ScheduleLog::where('task_name', $this->getTaskName($task))
    ->whereNull('finished_at')
    ->latest('started_at')
    ->first();

    if ($log) {
      $log->update([
        'finished_at' => now(),
        'exit_code' => $task->exitCode,
        'duration' => $duration,
        'output' => $this->getOutput($task),
        'error' => $task->exitCode !== 0 ? 'Task failed with exit code ' . $task->exitCode : null,
      ]);
    }

    unset($this->startTimes[$taskId]);
  }

  protected function getTaskId(ScheduledEvent $task) {
    // Kombinasi unik untuk identifikasi task
    return spl_object_hash($task);
  }

  protected function getTaskName(ScheduledEvent $task) {
    // Prioritas: command -> description -> expression
    if ($task->command) {
      // Contoh: 'backup:run' atau 'php artisan backup:run'
      $command = trim($task->command);
      if (str_starts_with($command, "'php artisan'")) {
        $command = substr($command, strlen("'php artisan'") + 1);
      }
      return $command;
    }

    if ($task->description) {
      return $task->description;
    }

    return $task->expression ?? 'unknown';
  }

  protected function getCommandString(ScheduledEvent $task) {
    // Menampilkan perintah lengkap untuk logging
    if ($task->command) {
      return $task->command;
    }
    if ($task->description) {
      return $task->description;
    }
    return $task->expression ?? 'unknown';
  }

  protected function getOutput(ScheduledEvent $task) {
    // Jika task mengirim output ke file, ambil sebagian isinya
    if ($task->output && file_exists($task->output)) {
      return file_get_contents($task->output, false, null, 0, 5000);
    }
    return null;
  }
}