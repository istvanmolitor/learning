<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Upload-then-reference for block media: the editor posts a file here first
 * and gets back a public-disk path, which it then includes as plain string
 * data in the block's own store/update request. This keeps
 * Admin\ContentBlockController free of multipart file handling entirely —
 * every block payload is plain JSON-shaped data, file or not.
 */
class CourseMediaController extends Controller
{
    public function store(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mp3,wav,ogg,pdf,docx,pptx,xlsx,txt'],
        ]);

        $file = $validated['file'];
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("course-media/{$course->id}/blocks", $name, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
