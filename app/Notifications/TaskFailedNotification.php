<?php

namespace Modules\Log\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TaskFailedNotification extends Notification
{
  protected string $taskName;
  protected int $exitCode;
  protected ?string $errorMessage;

  /**
  * Create a new notification instance.
  *
  * @param string $taskName
  * @param int $exitCode
  * @param string|null $errorMessage
  */
  public function __construct(string $taskName, int $exitCode, ?string $errorMessage = null) {
    $this->taskName = $taskName;
    $this->exitCode = $exitCode;
    $this->errorMessage = $errorMessage;
  }

  /**
  * Get the notification's delivery channels.
  *
  * @param mixed $notifiable
  * @return array
  */
  public function via($notifiable): array
  {
    $channels = config("log.notifications.channels", "database");
    return explode(",", trim($channels));
  }

  /**
  * Get the database representation of the notification.
  *
  * @param mixed $notifiable
  * @return array
  */
  public function toDatabase($notifiable): array
  {
    return [
      'task_name' => $this->taskName,
      'exit_code' => $this->exitCode,
      'error_message' => $this->errorMessage,
      'occurred_at' => now()->toIso8601String(),
    ];
  }

  /**
  * Get the mail representation of the notification.
  *
  * @param mixed $notifiable
  * @return \Illuminate\Notifications\Messages\MailMessage
  */
  public function toMail($notifiable): MailMessage
  {
    $mail = (new MailMessage)
    ->subject("Scheduled Task Failed: {$this->taskName}")
    ->greeting("Hello {$notifiable->name},")
    ->line("A scheduled task has failed with the following details:")
    ->line("**Task Name:** {$this->taskName}")
    ->line("**Exit Code:** {$this->exitCode}");

    if ($this->errorMessage) {
      $mail->line("**Error Message:**")
      ->line($this->errorMessage);
    }

    return $mail->action('View Server Logs', url('/'))
    ->line('Please investigate the issue.');
  }

  /**
  * Get the telegram representation of the notification.
  *
  * @param mixed $notifiable
  * @return \Illuminate\Notifications\Messages\TelegramMessage
  */
  public function toTelegram($notifiable): TelegramMessage
  {
    $message = "❌ *Task Failed*\n"
    . "*Name:* {$this->taskName}\n"
    . "*Exit Code:* {$this->exitCode}\n";

    if ($this->errorMessage) {
      $message .= "*Error:*\n```\n" . $this->errorMessage . "\n```";
    }

    return [
      "text" => $message,
      "parse_mode" => "MarkdownV2"
    ];
  }
}