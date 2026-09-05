<?php

namespace Modules\Log\Console;

use Illuminate\Console\Command;
use Modules\Log\Models\ScheduleLog;
use Carbon\Carbon;

class CleanScheduleLogs extends Command
{
  /**
  * Nama dan signature command.
  *
  * @var string
  */
  protected $signature = 'log:clean
                            {period? : Periode log yang akan dihapus (last-day, last-week, last-month, last-year)}
                            {--force : Lewati konfirmasi}';

  /**
  * Deskripsi command.
  *
  * @var string
  */
  protected $description = 'Menghapus log yang lebih tua dari periode tertentu';

  /**
  * Jalankan command.
  *
  * @return int
  */
  public function handle(): int
  {
    $period = strtolower($this->argument('period') ?? 'last-week');
    $force = $this->option('force');

    // Mapping periode ke jumlah hari
    $periodMap = [
      'last-day' => 1,
      'last-week' => 7,
      'last-month' => 30,
      'last-year' => 365,
    ];

    if (!isset($periodMap[$period])) {
      $this->error("Periode tidak valid: {$period}");
      $this->line('Gunakan salah satu: ' . implode(', ', array_keys($periodMap)));
      return 1;
    }

    $days = $periodMap[$period];
    $cutoff = Carbon::now()->subDays($days);

    // Hitung jumlah log yang akan dihapus
    $count = ScheduleLog::where('created_at', '<', $cutoff)->count();

    if ($count === 0) {
      $this->info("Tidak ada log yang lebih tua dari {$days} hari.");
      return 0;
    }

    $this->warn("Akan menghapus {$count} log yang lebih tua dari {$days} hari (sebelum {$cutoff->format('Y-m-d H:i:s')}).");

    // Konfirmasi
    if (!$force && !$this->confirm('Lanjutkan penghapusan?')) {
      $this->info('Penghapusan dibatalkan.');
      return 0;
    }

    // Hapus log
    $deleted = ScheduleLog::where('created_at', '<', $cutoff)->delete();

    $this->info("✅ Berhasil menghapus {$deleted} log.");
    return 0;
  }
}