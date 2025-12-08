<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\InvitationTemplate;
use Illuminate\Http\JsonResponse;

class InvitationTemplateController extends Controller
{
    /**
     * Display a listing of available invitation templates.
     */
    public function index(): JsonResponse
    {
        $templates = InvitationTemplate::with('products')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'product_id' => $template->products()->where('product_type', 'digital')->first()?->id,
                    'slug' => $template->slug,
                    'name' => $template->name,
                    'description' => $template->description,
                    'thumbnail_image' => $template->thumbnail_image,
                    'price' => $template->price,
                    'template_component' => $template->template_component,
                    'usage_count' => $template->usage_count,
                ];
            });

        return response()->json([
            'message' => 'Templates retrieved successfully',
            'data' => $templates,
        ]);
    }

    /**
     * Display the specified invitation template.
     */
    public function show(string $slug): JsonResponse
    {
        $template = InvitationTemplate::with('products')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return response()->json([
                'message' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Template retrieved successfully',
            'data' => [
                'id' => $template->id,
                'product_id' => $template->products()->where('product_type', 'digital')->first()?->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'description' => $template->description,
                'thumbnail_image' => $template->thumbnail_image,
                'price' => $template->price,
                'template_component' => $template->template_component,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at->toISOString(),
            ],
        ]);
    }
}
