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
        $templates = InvitationTemplate::with('products.variants')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($template) {
                $product = $template->products()->where('product_type', 'digital')->first();
                $variant = $product?->variants->first();

                return [
                    'id' => $template->id,
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'slug' => $template->slug,
                    'name' => $template->name,
                    'description' => $template->description,
                    'thumbnail_image' => url('media/'.$template->thumbnail_image),
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
        $template = InvitationTemplate::with('products.variants')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return response()->json([
                'message' => 'Template not found',
            ], 404);
        }

        $product = $template->products()->where('product_type', 'digital')->first();
        $variant = $product?->variants->first();

        return response()->json([
            'message' => 'Template retrieved successfully',
            'data' => [
                'id' => $template->id,
                'product_id' => $product?->id,
                'variant_id' => $variant?->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'description' => $template->description,
                'thumbnail_image' => url('media/'.$template->thumbnail_image),
                'price' => $template->price,
                'template_component' => $template->template_component,
                'usage_count' => $template->usage_count,
                'created_at' => $template->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get customization fields for a template.
     *
     * Returns only active fields ordered by display_order for customers to fill.
     */
    public function getFields(int $templateId): JsonResponse
    {
        $template = InvitationTemplate::where('id', $templateId)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return response()->json([
                'message' => 'Template not found',
            ], 404);
        }

        // Get only active fields, ordered
        $fields = $template->fields()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(function ($field) {
                return [
                    'id' => $field->id,
                    'field_key' => $field->field_key,
                    'field_label' => $field->field_label,
                    'field_type' => $field->field_type,
                    'field_category' => $field->field_category,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->default_value,
                    'validation_rules' => $field->validation_rules,
                    'help_text' => $field->help_text,
                    'display_order' => $field->display_order,
                ];
            });

        // Group fields by category for easier rendering
        $groupedFields = $fields->groupBy('field_category');

        return response()->json([
            'message' => 'Template fields retrieved successfully',
            'data' => [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'has_custom_fields' => $template->has_custom_fields,
                'fields' => $fields,
                'grouped_fields' => $groupedFields,
            ],
        ]);
    }
}
