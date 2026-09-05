<?php

namespace Modules\Log\Console;

use Illuminate\Console\Command;
use Modules\Log\Models\ScheduleLog;

class ShowScheduleLogs extends Command
{
  /**
  * Nama dan signature command.
  *
  * @var string
  */
  protected $signature = 'app:log
                            {--limit=10 : Jumlah baris log terakhir yang ditampilkan}
                            {--task= : Filter berdasarkan nama task}
                            {--status= : Filter status (success, failed, running, all)}
                            {--days= : Hanya tampilkan log dalam N hari terakhir}';

  /**
  * Deskripsi command.
  *
  * @var string
  */
  protected $description = 'Menampilkan total log dan beberapa baris log terakhir';

  /**
  * Jalankan command.
  *
  * @return int
  */
  public function handle(): int
  {
    $limit = (int) $this->option('limit');
    $task = $this->option('task');
    $status = $this->option('status');
    $days = $this->option('days') ? (int) $this->option('days') : null;

    // Bangun query dasar
    $query = ScheduleLog::query();

    // Terapkan filter task
    if ($task) {
      $query->forTask($task);
    }

    // Terapkan filter status
    if ($status && $status !== 'all') {
      switch ($status) {
        case 'success':
          $query->successful();
          break;
        case 'failed':
          $query->failed();
          break;
        case 'running':
          $query->running();
          break;
        default:
          $this->error("Status tidak valid: {$status}. Gunakan: success, failed, running, all");
          return 1;
        }
      }

      // Terapkan filter hari
      if ($days) {
        $query->lastDays($days);
      }

      // Hitung total log sesuai filter
      $total = $query->count();

      // Ambil log terakhir sesuai limit
      $logs = $query->latest()->limit($limit)->get();

      // Tampilkan informasi total
      $this->info('📊 Total log: ' . $total);
      $this->newLine();

      if ($logs->isEmpty()) {
        $this->warn('Tidak ada log yang cocok dengan filter.');
        return 0;
      }

      // Siapkan data untuk tabel
      $rows = $logs->map(function ($log) {
        $status = '⚠️ Running';
        if ($log->is_successful) {
          $status = '✅ Success';
        } elseif ($log->exit_code !== null) {
          $status = '❌ Failed';
        }

        $duration = $log->duration !== null ? number_format($log->duration, 2) . ' s' : '-';

        return [
          'ID' => $log->id,
          'Task' => $log->task_name,
          'Command' => $log->command,
          'Exit Code' => $log->exit_code ?? '-',
          'Started At' => $log->started_at?->format('Y-m-d H:i:s') ?? '-',
          'Duration' => $duration,
          'Triggered By' => $log->triggered_by ?? '-',
          'Status' => $status,
        ];
      });

      // Tampilkan tabel
      $this->table(
        ['ID',
          'Task',
          'Command',
          'Exit Code',
          'Started At',
          'Duration',
          'Triggered By',
          'Status'],
        $rows
      );

      return 0;
    }
  }