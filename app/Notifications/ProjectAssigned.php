<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAssigned extends Notification
{
    use Queueable;

    /**
     * The project that was assigned.
     *
     * @var \App\Models\Project
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
     */
    public function __construct(Project $project, $hourlyRate)
    {
        $this->project = $project;
        $this->hourlyRate = $hourlyRate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Only send if user has enabled notifications
        if (!$notifiable->notify_on_project_assignment) {
            return [];
        }
        
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $projectUrl = url('/projects');
        
        return (new MailMessage)
                    ->subject('You have been assigned to a new project')
                    ->greeting('Hello ' . $notifiable->name . '!')
                    ->line('You have been assigned to the project: **' . $this->project->name . '**')
                    ->line('Customer: ' . ($this->project->customer ? $this->project->customer->name : 'N/A'))
                    ->line('Your hourly rate for this project: $' . number_format($this->hourlyRate, 2))
                    ->when($this->project->deadline, function ($mail) {
                        return $mail->line('Deadline: ' . $this->project->deadline->format('F j, Y'));
                    })
                    ->when($this->project->description, function ($mail) {
                        return $mail->line('Description: ' . $this->project->description);
                    })
                    ->action('View Project', $projectUrl)
                    ->line('Thank you for your continued work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'customer_name' => $this->project->customer ? $this->project->customer->name : null,
            'hourly_rate' => $this->hourlyRate,
        ];
    }
}
