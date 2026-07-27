<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAssigned extends Notification
{
    use Queueable;

    /**
     * The project that was assigned.
     *
     * @var Project
     */
    protected $project;

    /**
     * The hourly rate for this assignment.
     *
     * @var float
     */
    protected $hourlyRate;

    /**
     * Create a new notification instance.
     *
     * @param  float|int|string  $hourlyRate
     */
    public function __construct(Project $project, $hourlyRate)
    {
        $this->project = $project;
        $this->hourlyRate = (float) $hourlyRate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        // Only send if user has enabled notifications
        if (! $notifiable->notify_on_project_assignment) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $projectUrl = url('/projects');

        $message = (new MailMessage)
            ->subject(__('notifications.project_assigned.subject'))
            ->greeting(__('notifications.project_assigned.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.project_assigned.assigned_to', ['project' => $this->project->name]))
            ->line(__('notifications.project_assigned.customer', ['customer' => $this->project->customer ? $this->project->customer->name : 'N/A']))
            ->line(__('notifications.project_assigned.hourly_rate', ['rate' => '$'.number_format($this->hourlyRate, 2)]));

        if ($this->project->deadline) {
            $message->line(__('notifications.project_assigned.deadline', ['deadline' => $this->project->deadline->format('F j, Y')]));
        }

        if ($this->project->description) {
            $message->line(__('notifications.project_assigned.description', ['description' => $this->project->description]));
        }

        return $message
            ->action(__('notifications.project_assigned.action'), $projectUrl)
            ->line(__('notifications.project_assigned.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'customer_name' => $this->project->customer ? $this->project->customer->name : null,
            'hourly_rate' => $this->hourlyRate,
        ];
    }
}
