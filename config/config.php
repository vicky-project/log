<?php

return [
  'name' => 'Log',
  "command_log" => [
    "enabled" => env("LOG_COMMAND_ENABLED", true),
    "timezone" => env("LOG_COMMAND_TIMEZONE", env("APP_TIMEZONE")),
  ],
  "pruning" => [
    "retention_days" => env("LOG_RETENTION_DAYS", 30)
  ]
];