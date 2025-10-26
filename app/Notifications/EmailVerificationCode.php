<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
{
    use Queueable;

    /**
     * The verification code.
     *
     * @var string
     */
    protected $code;

    /**
     * Create a new notification instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject(__('notifications.email_verification_code.subject'))
                    ->greeting(__('notifications.email_verification_code.greeting', ['name' => $notifiable->name]))
                    ->line(__('notifications.email_verification_code.requested'))
                    ->line(__('notifications.email_verification_code.code_is'))
                    ->line('**' . $this->code . '**')
                    ->line(__('notifications.email_verification_code.expires'))
                    ->line(__('notifications.email_verification_code.ignore'))
                    ->line(__('notifications.email_verification_code.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
