<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Backup $backup) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fullPath = storage_path('app/' . $this->backup->path);

        return (new MailMessage)
            ->subject('Database Backup Completed – ' . $this->backup->filename)
            ->greeting('Hello Admin,')
            ->line('A database backup has been completed successfully.')
            ->line('**Filename:** ' . $this->backup->filename)
            ->line('**File Size:** ' . $this->backup->size_for_humans)
            ->line('**Date & Time:** ' . $this->backup->created_at->format('F d, Y \a\t h:i A'))
            ->line('The backup file is attached to this email.')
            ->attach($fullPath, [
                'as' => $this->backup->filename,
                'mime' => 'application/sql',
            ])
            ->salutation('— ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'backup_id' => $this->backup->id,
            'filename' => $this->backup->filename,
            'file_size' => $this->backup->file_size,
        ];
    }
}
