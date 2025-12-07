<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvitationTemplateController extends Controller
{
    /**
     * Display a listing of invitation templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InvitationTemplate::query()->withCount('invitations');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $templates = $query->latest()->get();

        return response()->json([
            'data' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'description' => $template->description,
                    'thumbnail_image' => $template->thumbnail_image,
                    'thumbnail_url' => $template->thumbnail_image
                        ? Storage::url($template->thumbnail_image)
                        : null,
                    'price' => $template->price,
                    'template_component' => $template->template_component,
                    'is_active' => $template->is_active,
                    'usage_count' => $template->usage_count,
                    'invitations_count' => $template->invitations_count,
                    'created_at' => $template->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:invitation_templates,slug',
            'description' => 'nullable|string',
            'thumbnail_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'price' => 'required|numeric|min:0',
            'template_component' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle image upload
        if ($request->hasFile('thumbnail_image')) {
            $path = $request->file('thumbnail_image')->store('invitation_templates', 'public');
            $data['thumbnail_image'] = $path;
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['usage_count'] = 0;

        $template = InvitationTemplate::create($data);

        return response()->json([
            'message' => 'Template undangan berhasil dibuat.',
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'thumbnail_image' => $template->thumbnail_image,
                'thumbnail_url' => $template->thumbnail_image
                    ? Storage::url($template->thumbnail_image)
                    : null,
                'price' => $template->price,
                'template_component' => $template->template_component,
                'is_active' => $template->is_active,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Display the specified template.
     */
    public function show(int $id): JsonResponse
    {
        $template = InvitationTemplate::withCount('invitations')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'thumbnail_image' => $template->thumbnail_image,
                'thumbnail_url' => $template->thumbnail_image
                    ? Storage::url($template->thumbnail_image)
                    : null,
                'price' => $template->price,
                'template_component' => $template->template_component,
                'is_active' => $template->is_active,
                'usage_count' => $template->usage_count,
                'invitations_count' => $template->invitations_count,
                'created_at' => $template->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = InvitationTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:invitation_templates,slug,'.$id,
            'description' => 'nullable|string',
            'thumbnail_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'price' => 'nullable|numeric|min:0',
            'template_component' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('thumbnail_image')) {
            // Delete old image
            if ($template->thumbnail_image) {
                Storage::disk('public')->delete($template->thumbnail_image);
            }
            $path = $request->file('thumbnail_image')->store('invitation_templates', 'public');
            $data['thumbnail_image'] = $path;
        }

        $template->update(array_filter($data));

        return response()->json([
            'message' => 'Template undangan berhasil diperbarui.',
            'data' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'thumbnail_image' => $template->thumbnail_image,
                'thumbnail_url' => $template->thumbnail_image
                    ? Storage::url($template->thumbnail_image)
                    : null,
                'price' => $template->price,
                'template_component' => $template->template_component,
                'is_active' => $template->is_active,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Remove the specified template (only if no active invitations exist).
     */
    public function destroy(int $id): JsonResponse
    {
        $template = InvitationTemplate::withCount('invitations')->findOrFail($id);

        // Prevent deletion if invitations exist
        if ($template->invitations_count > 0) {
            return response()->json([
                'message' => 'Tidak dapat menghapus template yang masih memiliki undangan aktif.',
            ], 409);
        }

        // Delete thumbnail image
        if ($template->thumbnail_image) {
            Storage::disk('public')->delete($template->thumbnail_image);
        }

        $template->delete();

        return response()->json([
            'message' => 'Template undangan berhasil dihapus.',
        ]);
    }

    /**
     * Toggle active status of template.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $template = InvitationTemplate::findOrFail($id);

        $template->update([
            'is_active' => ! $template->is_active,
        ]);

        return response()->json([
            'message' => $template->is_active
                ? 'Template berhasil diaktifkan.'
                : 'Template berhasil dinonaktifkan.',
            'data' => [
                'id' => $template->id,
                'is_active' => $template->is_active,
            ],
        ]);
    }
}
