<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoProject;
use Illuminate\Http\Request;

class VideoProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = VideoProject::query()->latest();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50]) ? $perPage : 10;

        $projects = $query->paginate($perPage)->through(fn($p) => [
            'id'         => $p->id,
            'title'      => $p->title,
            'status'     => $p->status,
            'resolution' => $p->video_resolution,
            'duration'   => $p->video_duration ? gmdate('i:s', $p->video_duration) : '—',
            'size'       => $p->video_file_size_human,
            'progress'   => $p->progress_percent,
            'created_at' => $p->created_at->format('Y-m-d'),
            'has_video'  => (bool) $p->output_video_path,
        ]);

        $stats = [
            'total'      => VideoProject::count(),
            'completed'  => VideoProject::where('status', 'completed')->count(),
            'processing' => VideoProject::whereIn('status', ['queued', 'processing'])->count(),
            'failed'     => VideoProject::where('status', 'failed')->count(),
        ];

        return response()->json([
            'data'       => $projects->items(),
            'pagination' => [
                'total'        => $projects->total(),
                'per_page'     => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'from'         => $projects->firstItem(),
                'to'           => $projects->lastItem(),
            ],
            'stats' => $stats,
        ]);
    }
}
