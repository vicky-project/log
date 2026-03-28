<?php
namespace Modules\Log\Constants;

class Permission {
  const VIEW_ACTIVITY_LOGS = "logs.activity.index";
  const VIEW_APP_LOGS = "logs.app.index";
  const VIEW_AUTH_LOGS = "logs.auth.index";

  public static function all():array {
    return [
      self::VIEW_ACTIVITY_LOGS => 'View activities logs module',
      self::VIEW_APP_LOGS => 'View application logs module',
      self::VIEW_AUTH_LOGS => 'View authentication logs module',
    ];
  }
}