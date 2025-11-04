<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ProfileIncompleteReminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProfileReminderJob implements ShouldQueue
{
    use Queueable;

    public $user;
    public $profileData;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $profileData)
    {
        $this->user = $user;
        $this->profileData = $profileData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Enviar notificación de recordatorio de perfil de forma asíncrona
        $this->user->notify(new ProfileIncompleteReminder($this->profileData));
    }
}
