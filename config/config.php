<?php

return [
  'name' => 'Log',
  "command_log" => [
    "enabled" => env("LOG_COMMAND_ENABLED", true),
    "timezone" => env("LOG_COMMAND_TIMEZONE", env("APP_TIMEZONE"))
  ]
];