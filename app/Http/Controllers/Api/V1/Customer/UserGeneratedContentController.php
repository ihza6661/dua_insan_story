<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserGeneratedContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserGeneratedContentController extends Controller
{
    /**
     * Get approved UGC (public endpoint)
     */
    public function index(Request $request)
    {
        $query = UserGeneratedContent::with(['user:id,name', 'product:id,name,slug'])
            ->approved()
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        // Filter by product if provided
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter featured only
        if ($request->boolean('featured')) {
            $query->featured();
        }

        $perPage = $request->input('per_page', 12);
        $ugc = $query->paginate($perPage);

        return response()->json([
            'message' => 'User generated content retrieved successfully',
            'data' => $ugc,
        ]);
    }

    /**
     * Submit new UGC (authenticated endpoint)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'caption' => 'nullable|string|max:500',
            'instagram_url' => 'nullable|url',
            'instagram_handle' => 'nullable|string|max:100',
            'product_id' => 'nullable|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'digital_invitation_id' => 'nullable|exists:digital_invitations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Upload image using dynamic disk (Cloudinary in production, local in dev)
        $image = $request->file('image');
        $disk = config('filesystems.user_uploads');
        $path = Storage::disk($disk)->put('ugc', $image);

        // Create UGC record
        $ugc = UserGeneratedContent::create([
            'user_id' => $request->user()->id,
            'order_id' => $request->order_id,
            'product_id' => $request->product_id,
            'digital_invitation_id' => $request->digital_invitation_id,
            'image_path' => $path,
            'caption' => $request->caption,
            'instagram_url' => $request->instagram_url,
            'instagram_handle' => $request->instagram_handle,
            'is_approved' => false, // Requires admin approval
            'is_featured' => false,
        ]);

        return response()->json([
            'message' => 'Foto berhasil dikirim! Akan muncul di galeri setelah disetujui admin.',
            'data' => $ugc,
        ], 201);
    }

    /**
     * Get user's own submitted UGC
     */
    public function mySubmissions(Request $request)
    {
        $ugc = UserGeneratedContent::where('user_id', $request->user()->id)
            ->with(['product:id,name,slug', 'order:id'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return response()->json([
            'message' => 'Your submissions retrieved successfully',
            'data' => $ugc,
        ]);
    }

    /**
     * Delete user's own UGC (only if not approved yet)
     */
    public function destroy(Request $request, UserGeneratedContent $ugc)
    {
        // Ensure user owns this UGC
        if ($ugc->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow deletion if not approved yet
        if ($ugc->is_approved) {
            return response()->json([
                'message' => 'Cannot delete approved content. Please contact admin.',
            ], 403);
        }

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
