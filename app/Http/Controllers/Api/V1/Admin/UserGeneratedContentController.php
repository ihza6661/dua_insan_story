<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserGeneratedContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserGeneratedContentController extends Controller
{
    /**
     * Get all UGC for admin (approved and pending)
     */
    public function index(Request $request)
    {
        $query = UserGeneratedContent::with(['user:id,name,email', 'product:id,name,slug'])
            ->orderByDesc('created_at');

        // Filter by approval status
        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        $perPage = $request->input('per_page', 15);
        $ugc = $query->paginate($perPage);

        return response()->json([
            'message' => 'User generated content retrieved successfully',
            'data' => $ugc,
        ]);
    }

    /**
     * Approve UGC
     */
    public function approve(Request $request, UserGeneratedContent $ugc)
    {
        $ugc->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Content approved successfully',
            'data' => $ugc->fresh(),
        ]);
    }

    /**
     * Unapprove UGC
     */
    public function unapprove(Request $request, UserGeneratedContent $ugc)
    {
        $ugc->update([
            'is_approved' => false,
            'approved_at' => null,
        ]);

        return response()->json([
            'message' => 'Content unapproved successfully',
            'data' => $ugc->fresh(),
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Request $request, UserGeneratedContent $ugc)
    {
        $ugc->update([
            'is_featured' => ! $ugc->is_featured,
        ]);

        return response()->json([
            'message' => $ugc->is_featured ? 'Content featured successfully' : 'Content unfeatured successfully',
            'data' => $ugc->fresh(),
        ]);
    }

    /**
     * Delete UGC (admin can delete any)
     */
    public function destroy(UserGeneratedContent $ugc)
    {
        // Use dynamic disk for user uploads
        $disk = config('filesystems.user_uploads');

        // Delete image from storage
        if ($ugc->image_path && Storage::disk($disk)->exists($ugc->image_path)) {
            Storage::disk($disk)->delete($ugc->image_path);
        }

        $ugc->delete();

        return response()->json([
            'message' => 'Content deleted successfully',
        ]);
    }
}
