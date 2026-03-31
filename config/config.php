<?php

return [
  'name' => 'Log',
  "command_log" => [
    "enabled" => env("LOG_COMMAND_ENABLED", true),
    "timezone" => env("LOG_COMMAND_TIMEZONE", env("APP_TIMEZONE")),
  ],
  "pruning" => [
    "enable" => env("LOG_PRUNING_ENABLED", true),
    "retention_days" => env("LOG_RETENTION_DAYS", 5)
  ]
];