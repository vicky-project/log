<?php
namespace Modules\Log\Http\Controllers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Log\Models\ScheduleLog;
use Carbon\Carbon;
use Cron\CronExpression;

class ScheduleMonitorController extends Controller
{
  protected $schedule;

  public function __construct(Schedule $schedule) {
    $this->schedule = $schedule;
  }

  /**
  * Tampilan utama
  */
  public function index() {
    $events = $this->schedule->events();
    $tasks = [];

    foreach ($events as $event) {
      $task = $this->enrichEvent($event);
      $tasks[] = $task;
    }

    // Group tasks by group (bisa dari description atau command)
    $groups = collect($tasks)->groupBy('group')->map->count()->toArray();

    // Statistik
    $totalTasks = count($tasks);
    $activeTasks = count(array_filter($tasks, fn($t) => $t['enabled']));
    $failedToday = ScheduleLog::whereDate('created_at', today())
    ->where('exit_code', '!=', 0)
    ->whereNotNull('exit_code')
    ->count();

    return view('log::schedule-monitor.index', compact('tasks', 'totalTasks', 'activeTasks', 'failedToday', 'groups'));
  }

  /**
  * Halaman log
  */
  public function logs(Request $request) {
    $taskName = $request->get('task');
    $logs = ScheduleLog::query()
    ->when($taskName, fn($q) => $q->where('task_name', $taskName))
    ->orderBy('created_at', 'desc')
    ->paginate(30)
    ->withQueryString();

    // Ambil daftar task yang ada di log
    $taskList = ScheduleLog::select('task_name')->distinct()->pluck('task_name', 'task_name')->toArray();

    return view('log::schedule-monitor.logs', compact('logs', 'taskList', 'taskName'));
  }

  /**
  * Menjalankan task secara manual
  */
  public function run(Request $request, $taskIdentifier) {
    $event = $this->findEventByIdentifier($taskIdentifier);
    if (!$event) {
      return response()->json(['success' => false, 'message' => 'Task not found'], 404);
    }

    // Hanya bisa menjalankan task yang memiliki command (bukan closure)
    if (!$event->command) {
      return response()->json(['success' => false, 'message' => 'Cannot run closure-based task manually'], 400);
    }

    $command = $event->command;
    // Bersihkan command string (hapus 'php artisan' jika ada)
    if (str_starts_with($command, "'php artisan'")) {
      $command = trim(str_replace("'php artisan'", '', $command));
    }
    $command = trim($command);

    // Log mulai
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
        'message' => $exitCode === 0 ? 'Task executed successfully' : 'Task failed with exit code ' . $exitCode,
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
  * Mengaktifkan/menonaktifkan task (simpan di cache)
  * Untuk demo, disable hanya untuk UI, tidak mempengaruhi scheduler otomatis
  */
  public function toggle($taskIdentifier) {
    $event = $this->findEventByIdentifier($taskIdentifier);
    if (!$event) {
      return response()->json(['success' => false, 'message' => 'Task not found'], 404);
    }

    $key = "schedule_monitor_disabled_" . $this->getTaskIdentifier($event);
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
  * API: Detail task + log terakhir
  */
  public function apiTaskDetail($taskIdentifier) {
    $event = $this->findEventByIdentifier($taskIdentifier);
    if (!$event) {
      return response()->json(['error' => 'Task not found'], 404);
    }

    $task = $this->enrichEvent($event);
    $lastLogs = ScheduleLog::where('task_name', $task['name'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

    return response()->json([
      'task' => $task,
      'recent_logs' => $lastLogs,
    ]);
  }

  /**
  * Helper: cari event berdasarkan identifier unik
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
  * Membuat identifier unik untuk event (gunakan command atau description)
  */
  protected function getTaskIdentifier($event) {
    if ($event->command) {
      return md5($event->command);
    }
    return md5($event->description ?? $event->expression);
  }

  /**
  * Mendapatkan nama task yang ramah (command atau description)
  */
  protected function getTaskName($event) {
    if ($event->command) {
      $cmd = $event->command;
      if (str_starts_with($cmd, "'php artisan'")) {
        $cmd = trim(str_replace("'php artisan'", '', $cmd));
      }
      return $cmd;
    }
    return $event->description ?? $event->expression;
  }

  /**
  * Memperkaya data event dengan informasi last run, next run, status, dll
  */
  protected function enrichEvent($event) {
    $taskName = $this->getTaskName($event);
    $identifier = $this->getTaskIdentifier($event);
    $cron = CronExpression::factory($event->expression);
    $nextRun = Carbon::instance($cron->getNextRunDate());
    $nextRunHuman = $nextRun->diffForHumans();

    $lastLog = ScheduleLog::where('task_name', $taskName)
    ->orderBy('created_at', 'desc')
    ->first();

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

    return [
      'identifier' => $identifier,
      'name' => $taskName,
      'label' => $this->getFriendlyLabel($event),
      'command' => $event->command,
      'description' => $event->description,
      'schedule' => $event->expression,
      'timezone' => $event->timezone ?? config('app.timezone'),
      'group' => $this->extractGroup($event),
      'enabled' => $enabled,
      'last_run' => $lastLog ? $lastLog->created_at : null,
      'last_status' => $status,
      'last_duration' => $lastLog ? $lastLog->duration : null,
      'next_run' => $nextRun,
      'next_run_human' => $nextRunHuman,
      'is_command' => !empty($event->command),
    ];
  }

  protected function getFriendlyLabel($event) {
    if ($event->description) {
      return $event->description;
    }
    $cmd = $this->getTaskName($event);
    // Ubah command seperti 'backup:run' menjadi 'Backup Run'
    $label = str_replace([':', '-', '_'], ' ', $cmd);
    return ucwords($label);
  }

  protected function extractGroup($event) {
    if ($event->description) {
      // Coba ambil kata pertama sebagai group
      $parts = explode(' ', $event->description);
      if (count($parts) > 1) {
        return $parts[0];
      }
    }
    $cmd = $this->getTaskName($event);
    if (str_contains($cmd, ':')) {
      return explode(':', $cmd)[0];
    }
    return 'General';
  }
}