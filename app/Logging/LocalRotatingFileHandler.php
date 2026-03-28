<?php

namespace Modules\Log\Logging;

use Monolog\Handler\RotatingFileHandler;
use DateTimeZone;
use DateTimeImmutable;

class LocalRotatingFileHandler extends RotatingFileHandler
{
  public function __construct(
    string $filename,
    int $maxFiles = 0,
    $level = \Monolog\Level::Debug,
    bool $bubble = true,
    ?int $filePermission = null,
    bool $useLocking = false,
    string $dateFormat = self::FILE_PER_DAY,
    string $filenameFormat = '{filename}-{date}',
    ?DateTimeZone $timezone = null
  ) {
    // Gunakan timezone lokal jika tidak disediakan
    if ($timezone === null) {
      $timezone = new DateTimeZone(date_default_timezone_get());
    }
    if ($filePermission === null) {
      $filePermission = 0777;
    }
    parent::__construct($filename, $maxFiles, $level, $bubble, $filePermission, $useLocking, $dateFormat, $filenameFormat, $timezone);
  }

  protected function getNextRotation(): DateTimeImmutable
  {
    // Hitung rotasi berikutnya berdasarkan timezone lokal
    $now = new DateTimeImmutable('now', $this->timezone);
    return $now->modify('+1 day')->setTime(0, 0, 0);
  }
}