<?php

namespace Modules\Log\Logging;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Carbon\Carbon;

class LocalTimezoneLogger
{
  /**
  * Customize the given logger instance.
  *
  * @param  \Illuminate\Log\Logger  $logger
  * @return void
  */
  public function __invoke($logger) {
    foreach ($logger->getHandlers() as $handler) {
      if ($handler instanceof RotatingFileHandler) {
        // Override method untuk menentukan filename berdasarkan waktu lokal
        $handler->setFilenameFormat(
          '{filename}-{date}',
          'Y-m-d'
        );
        // Atur timezone lokal saat membuat tanggal
        $handler->setDateFilenameGenerator(function () {
          return Carbon::now(env("APP_TIMEZONE", 'Asia/Makassar'))->format('Y-m-d');
        });
      }
    }
  }
}