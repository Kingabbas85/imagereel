<?php

// ================================================================
// FILE 1: app/Events/VideoProgressUpdated.php
// COMMAND: php artisan make:event VideoProgressUpdated
// ================================================================
namespace App\Events;
 
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
 
class VideoProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public function __construct(
        public int    $projectId,
        public int    $userId,
        public int    $progress,
        public string $step
    ) {}
 
    // Sirf us user ka private channel — doosre log nahi dekh saktey
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }
 
    public function broadcastAs(): string
    {
        return 'video.progress';
    }
 
    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'progress'   => $this->progress,
            'step'       => $this->step,
        ];
    }
}
 