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
    $taskId = spl_object_hash($task);
    $this->startTimes[$taskId] = microtime(true);

    $taskName = $this->getTaskName($task);
    $command = $this->getCommandString($task);

    ScheduleLog::create([
      'task_name' => $taskName,
      'command' => $command,
      'started_at' => now(),
      'triggered_by' => 'schedule',
    ]);
  }

  protected function handleFinished(ScheduledEvent $task) {
    $taskId = spl_object_hash($task);
    $startTime = $this->startTimes[$taskId] ?? null;
    $duration = $startTime ? round(microtime(true) - $startTime, 2) : null;

    $taskName = $this->getTaskName($task);
    $log = ScheduleLog::where('task_name', $taskName)
    ->whereNull('finished_at')
    ->latest('started_at')
    ->first();

    if ($log) {
      $output = $this->getOutput($task);
      $errorMessage = null;
      if ($task->exitCode !== 0) {
        // Ambil pesan error dari log Laravel (5 menit terakhir) sebagai fallback
        $errorMessage = $this->getRecentErrorFromLog($taskName);
        if (!$errorMessage) {
          $errorMessage = "Task failed with exit code {$task->exitCode}. No additional output captured.";
        }
      }

      $log->update([
        'finished_at' => now(),
        'exit_code' => $task->exitCode,
        'duration' => $duration,
        'output' => $output,
        'error' => $errorMessage,
      ]);
    }
    unset($this->startTimes[$taskId]);
  }

  protected function getRecentErrorFromLog($taskName) {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) return null;

    $lines = file($logFile);
    $relevantLines = [];
    $searchTime = now()->subMinutes(5);
    foreach (array_reverse($lines) as $line) {
      if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
        $lineTime = Carbon::parse($matches[1]);
        if ($lineTime->lt($searchTime)) break;
        if (str_contains($line, $taskName) || str_contains($line, 'error')) {
          $relevantLines[] = trim($line);
        }
      }
      if (count($relevantLines) >= 5) break;
    }
    return !empty($relevantLines) ? implode("\n", array_reverse($relevantLines)) : null;
  }

  protected function getTaskName(ScheduledEvent $task) {
    // Gunakan method yang sama seperti di controller untuk konsistensi
    $rawCommand = $task->command ?? '';
    if ($rawCommand) {
      return $this->extractArtisanCommand($rawCommand);
    }
    return $task->description ?? $task->expression ?? 'unknown';
  }

  protected function getCommandString(ScheduledEvent $task) {
    return $task->command ?? $task->description ?? $task->expression ?? 'unknown';
  }

  protected function getOutput(ScheduledEvent $task) {
    if ($task->output && file_exists($task->output)) {
      return file_get_contents($task->output, false, null, 0, 5000);
    }
    return null;
  }

  protected function extractArtisanCommand($commandString) {
    $commandString = trim($commandString);
    // Hapus tanda kutip di awal/akhir
    $cleaned = preg_replace('/^[\'"]+|[\'"]+$/', '', $commandString);
    $cleaned = str_replace(["'", '"'], '', $cleaned);
    if (preg_match('/\bartisan\s+(.+)/', $cleaned, $matches)) {
      return trim($matches[1]);
    }
    return $cleaned;
  }
}