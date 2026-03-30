<?php

namespace Modules\Log\Http\Controllers;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use DateTimeZone;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\CommandEvent;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\ExecEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Log\Models\ScheduleLog;
use ReflectionClass;
use ReflectionFunction;

class ScheduleMonitorController extends Controller
{
  protected $schedule;

  public function __construct(Schedule $schedule) {
    $this->schedule = $schedule;
  }

  /**
  * Halaman utama
  */
  public function index() {
    $eventsData = $this->getEventsData();
    $tasks = $eventsData->toArray();

    $groups = $eventsData->groupBy('group')->map->count()->toArray();
    $totalTasks = count($tasks);
    $activeTasks = count(array_filter($tasks, fn($t) => $t['enabled']));
    $failedToday = ScheduleLog::whereDate('created_at', today())
    ->where('exit_code', '!=', 0)
    ->whereNotNull('exit_code')
    ->count();

    return view('log::schedule-monitor.index', compact('tasks', 'totalTasks', 'activeTasks', 'failedToday', 'groups'));
  }

  /**
  * Halaman log eksekusi
  */
  public function logs(Request $request) {
    $taskName = $request->get('task');
    $logs = ScheduleLog::query()
    ->when($taskName, fn($q) => $q->where('task_name', $taskName))
    ->orderBy('created_at', 'desc')
    ->paginate(30);

    $taskList = ScheduleLog::select('task_name')->distinct()->pluck('task_name', 'task_name')->toArray();

    return view('log::schedule-monitor.logs', compact('logs', 'taskList', 'taskName'));
  }

  /**
  * Menjalankan task secara manual
  */
  public function run(Request $request, $identifier) {
    $event = $this->findEventByIdentifier($identifier);
    if (!$event) {
      return response()->json(['success' => false, 'message' => 'Task not found'], 404);
    }

    if ($event instanceof CommandEvent) {
      return $this->runCommandEvent($event);
    } elseif ($event instanceof ExecEvent) {
      return $this->runExecEvent($event);
    } else {
      // CallbackEvent atau jenis lain tidak bisa dijalankan manual
      return response()->json(['success' => false, 'message' => 'Cannot run callback or custom event manually'], 400);
    }
  }

  /**
  * Menjalankan CommandEvent (Artisan command)
  */
  protected function runCommandEvent(CommandEvent $event) {
    $command = $event->command;
    $log = ScheduleLog::create([
      'task_name' => $this->getTaskName($event),
      'command' => $command,
      'started_at' => now(),
      'triggered_by' => 'manual',
    ]);

    $startTime = microtime(true);
    try {
      $exitCode = Artisan::call($command);
      $output = Artisan::output();
      $duration = microtime(true) - $startTime;

      $log->update([
        'finished_at' => now(),
        'exit_code' => $exitCode,
        'output' => $output,
        'duration' => round($duration, 2),
      ]);

      return response()->json([
        'success' => $exitCode === 0,
        'message' => $exitCode === 0 ? 'Task executed successfully' : "Task failed with exit code {$exitCode}",
        'exit_code' => $exitCode,
        'output' => $output,
      ]);
    } catch (\Exception $e) {
      $log->update([
        'finished_at' => now(),
        'exit_code' => 1,
        'error' => $e->getMessage(),
        'duration' => round(microtime(true) - $startTime, 2),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Task execution failed: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
  * Menjalankan ExecEvent (shell command)
  */
  protected function runExecEvent(ExecEvent $event) {
    $command = $event->command;
    $log = ScheduleLog::create([
      'task_name' => $this->getTaskName($event),
      'command' => $command,
      'started_at' => now(),
      'triggered_by' => 'manual',
    ]);

    $startTime = microtime(true);
    try {
      $output = [];
      $exitCode = 0;
      exec($command . ' 2>&1', $output, $exitCode);
      $duration = microtime(true) - $startTime;
      $outputString = implode("\n", $output);

      $log->update([
        'finished_at' => now(),
        'exit_code' => $exitCode,
        'output' => $outputString,
        'duration' => round($duration, 2),
      ]);

      return response()->json([
        'success' => $exitCode === 0,
        'message' => $exitCode === 0 ? 'Task executed successfully' : "Task failed with exit code {$exitCode}",
        'exit_code' => $exitCode,
        'output' => $outputString,
      ]);
    } catch (\Exception $e) {
      $log->update([
        'finished_at' => now(),
        'exit_code' => 1,
        'error' => $e->getMessage(),
        'duration' => round(microtime(true) - $startTime, 2),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Task execution failed: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
  * Toggle enable/disable (disimpan di cache)
  */
  public function toggle($identifier) {
    $event = $this->findEventByIdentifier($identifier);
    if (!$event) {
      return response()->json(['success' => false, 'message' => 'Task not found'], 404);
    }

    $key = "schedule_monitor_disabled_" . $identifier;
    $isDisabled = Cache::get($key, false);
    if ($isDisabled) {
      Cache::forget($key);
      $enabled = true;
    } else {
      Cache::put($key, true, 3600 * 24 * 30);
      $enabled = false;
    }

    return response()->json(['success' => true, 'enabled' => $enabled]);
  }

  /**
  * API detail task
  */
  public function apiTaskDetail($identifier) {
    $event = $this->findEventByIdentifier($identifier);
    if (!$event) {
      return response()->json(['error' => 'Task not found'], 404);
    }

    $task = $this->formatEventData($event, new DateTimeZone(config('app.timezone')));
    $lastLogs = ScheduleLog::where('task_name', $task['name'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

    return response()->json([
      'task' => $task,
      'recent_logs' => $lastLogs,
    ]);
  }

  // ==================== Helper Methods ====================

  /**
  * Mengambil semua event yang dijadwalkan dan memformatnya
  */
  protected function getEventsData() {
    $events = $this->schedule->events();
    $timezone = new DateTimeZone(config('app.timezone'));
    $data = [];

    foreach ($events as $event) {
      $data[] = $this->formatEventData($event, $timezone);
    }

    return collect($data);
  }

  /**
  * Memformat satu event menjadi array data
  */
  protected function formatEventData(ScheduledEvent $event, DateTimeZone $timezone) {
    $nextDueDate = $this->getNextDueDateForEvent($event, $timezone);
    $repeatExpression = $event->isRepeatable() ? "{$event->repeatSeconds}s" : '';
    $command = $this->getCommandDisplay($event);
    $description = $event->description ?? '';

    $identifier = $this->getTaskIdentifier($event);
    $taskName = $this->getTaskName($event);

    $lastLog = ScheduleLog::where('task_name', $taskName)->orderBy('created_at', 'desc')->first();

    $key = "schedule_monitor_disabled_" . $identifier;
    $isDisabled = Cache::get($key, false);
    $enabled = !$isDisabled;

    $status = 'pending';
    if ($lastLog) {
      if ($lastLog->finished_at && $lastLog->exit_code === 0) {
        $status = 'success';
      } elseif ($lastLog->finished_at && $lastLog->exit_code !== 0) {
        $status = 'failed';
      } elseif (!$lastLog->finished_at) {
        $status = 'running';
      }
    }

    // Deteksi jenis event (untuk keperluan view)
    $isCommandEvent = $event instanceof CommandEvent;
    $isExecEvent = $event instanceof ExecEvent;
    $isCallbackEvent = $event instanceof CallbackEvent;

    return [
      'identifier' => $identifier,
      'name' => $taskName,
      'expression' => $event->expression,
      'repeat' => $repeatExpression,
      'command' => $command,
      'description' => $description,
      'next_due' => $nextDueDate,
      'next_due_human' => $nextDueDate->diffForHumans(),
      'status' => $status,
      'enabled' => $enabled,
      'has_mutex' => $event->mutex->exists($event),
      'last_duration' => $lastLog ? $lastLog->duration : null,
      'last_run' => $lastLog ? $lastLog->created_at : null,
      'group' => $this->extractGroup($event),
      'is_command_event' => $isCommandEvent,
      'is_exec_event' => $isExecEvent,
      'is_callback_event' => $isCallbackEvent,
      'is_command' => !empty($event->command) && $isCommandEvent,
      // Hanya CommandEvent yang bisa dijalankan manual
    ];
  }

  /**
  * Mendapatkan next due date untuk event (mirip dengan schedule:list)
  */
  protected function getNextDueDateForEvent(ScheduledEvent $event, DateTimeZone $timezone) {
    $nextDueDate = Carbon::instance(
      (new CronExpression($event->expression))
      ->getNextRunDate(Carbon::now()->setTimezone($event->timezone ?? config('app.timezone')))
      ->setTimezone($timezone)
    );

    if (!$event->isRepeatable()) {
      return $nextDueDate;
    }

    $previousDueDate = Carbon::instance(
      (new CronExpression($event->expression))
      ->getPreviousRunDate(Carbon::now()->setTimezone($event->timezone ?? config('app.timezone')), allowCurrentDate: true)
      ->setTimezone($timezone)
    );

    $now = Carbon::now()->setTimezone($event->timezone ?? config('app.timezone'));

    if (!$now->copy()->startOfMinute()->eq($previousDueDate)) {
      return $nextDueDate;
    }

    return $now->endOfSecond()->ceilSeconds($event->repeatSeconds);
  }

  /**
  * Mendapatkan representasi command yang akan ditampilkan di UI
  * Pendekatan cerdas: prioritaskan properti command, lalu khusus untuk CallbackEvent
  */
  protected function getCommandDisplay(ScheduledEvent $event) {
    // Jika event memiliki properti command (CommandEvent atau ExecEvent)
    if ($event->command) {
      $command = $event->command;
      // Normalisasi command untuk menghilangkan 'php artisan' jika ada
      if (str_starts_with($command, "'php artisan'")) {
        $command = trim(str_replace("'php artisan'", '', $command));
      }
      return $command;
    }

    // Khusus untuk CallbackEvent
    if ($event instanceof CallbackEvent) {
      $summary = $event->getSummaryForDisplay();
      if (in_array($summary, ['Closure', 'Callback'])) {
        return 'Closure at: ' . $this->getClosureLocation($event);
      }
      return $summary;
    }

    // Fallback: gunakan description atau expression
    return $event->description ?? $event->expression ?? 'Unknown';
  }

  /**
  * Mendapatkan lokasi file closure (untuk CallbackEvent)
  */
  protected function getClosureLocation(CallbackEvent $event) {
    $callback = (new ReflectionClass($event))->getProperty('callback')->getValue($event);

    if ($callback instanceof Closure) {
      $function = new ReflectionFunction($callback);
      return sprintf(
        '%s:%s',
        str_replace(base_path() . DIRECTORY_SEPARATOR, '', $function->getFileName() ?: ''),
        $function->getStartLine()
      );
    }

    if (is_string($callback)) {
      return $callback;
    }

    if (is_array($callback)) {
      $className = is_string($callback[0]) ? $callback[0] : $callback[0]::class;
      return sprintf('%s::%s', $className, $callback[1]);
    }

    return sprintf('%s::__invoke', $callback::class);
  }

  /**
  * Mendapatkan nama task yang akan disimpan di log
  */
  protected function getTaskName(ScheduledEvent $event) {
    if ($event->command) {
      $cmd = $event->command;
      if (str_starts_with($cmd, "'php artisan'")) {
        $cmd = trim(str_replace("'php artisan'", '', $cmd));
      }
      return $cmd;
    }

    if ($event->description) {
      return $event->description;
    }

    return $event->expression ?? 'unknown';
  }

  /**
  * Mendapatkan identifier unik untuk event (digunakan untuk toggle & run)
  */
  protected function getTaskIdentifier(ScheduledEvent $event) {
    if ($event->command) {
      return md5($event->command);
    }
    return md5($event->description ?? $event->expression);
  }

  /**
  * Mencari event berdasarkan identifier
  */
  protected function findEventByIdentifier($identifier) {
    foreach ($this->schedule->events() as $event) {
      if ($this->getTaskIdentifier($event) === $identifier) {
        return $event;
      }
    }
    return null;
  }

  /**
  * Ekstrak group dari event (untuk tab grouping)
  */
  protected function extractGroup(ScheduledEvent $event) {
    $command = $this->getCommandDisplay($event);
    if (str_contains($command, ':')) {
      return explode(':', $command)[0];
    }
    if ($event->description) {
      return explode(' ', $event->description)[0];
    }
    return 'General';
  }
}