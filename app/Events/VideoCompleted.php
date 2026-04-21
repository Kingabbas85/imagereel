<?php
 
// ================================================================
// FILE 2: app/Events/VideoCompleted.php
// COMMAND: php artisan make:event VideoCompleted
// ================================================================
namespace App\Events;
 
use App\Models\VideoProject;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
 
class VideoCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
 
    public function __construct(public VideoProject $project) {}
 
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->project->user_id}"),
        ];
    }
 
    public function broadcastAs(): string
    {
        return 'video.completed';
    }
 
    public function broadcastWith(): array
    {
        return [
            'project_id'  => $this->project->id,
            'title'       => $this->project->title,
            'video_url'   => $this->project->video_url,
            'file_size'   => $this->project->video_file_size_human,
        ];
    }
}