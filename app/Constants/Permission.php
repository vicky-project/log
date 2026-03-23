<?php
namespace Modules\Log\Constants;

class Permission {
  const VIEW_APP_LOGS = "logs.app.index";

  public static function all():array {
    return [
      self::VIEW_APP_LOGS => 'View application logs module',
    ];
  }
}