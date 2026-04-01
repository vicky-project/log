<?php

return [
  'name' => 'Log',
  "back_to_system_url" => (string) env("APP_URL") . "/admin/home",
  "command_log" => [
    "enabled" => env("LOG_COMMAND_ENABLED", true),
    "timezone" => env("LOG_COMMAND_TIMEZONE", env("APP_TIMEZONE")),
  ],
  "pruning" => [
    "retention_days" => env("LOG_RETENTION_DAYS", 30)
  ],
  "notifications" => [
    "user_id" => env("LOG_NOTIFICATION_IDS", 1), // User Id in table users. Comma separated
    "channels" => env("LOG_NOTIFICATION_CHANNELS", "database")
  ]
];