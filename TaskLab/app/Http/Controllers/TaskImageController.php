<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskImageController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'], // 10 MB
        ]);

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = 'task_' . $task->id . '_' . Str::random(12) . '.' . $extension;
        $path = 'task-images/' . $filename;

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $image = TaskImage::create([
            'task_id'      => $task->id,
            'original_name' => $file->getClientOriginalName(),
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'id'            => $image->id,
            'url'           => $image->url,
            'original_name' => $image->original_name,
        ], 201);
    }

    public function destroy(Task $task, TaskImage $image)
    {
        abort_if($image->task_id !== $task->id, 404);

        Storage::disk('public')->delete($image->storage_path);
        $image->delete();

        return response()->json(['status' => 'ok']);
    }
}
