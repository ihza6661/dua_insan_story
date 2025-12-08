<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateFieldRequest;
use App\Http\Requests\UpdateTemplateFieldRequest;
use App\Models\InvitationTemplate;
use App\Models\TemplateField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateFieldController extends Controller
{
    /**
     * Display a listing of fields for a specific template.
     *
     * GET /api/v1/admin/invitation-templates/{templateId}/fields
     */
    public function index($templateId): JsonResponse
    {
        // Verify template exists
        $template = InvitationTemplate::findOrFail($templateId);

        $fields = TemplateField::where('template_id', $templateId)
            ->orderBy('display_order')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'message' => 'Fields retrieved successfully',
            'data' => [
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'has_custom_fields' => $template->has_custom_fields,
                ],
                'fields' => $fields,
            ],
        ]);
    }

    /**
     * Store a newly created field.
     *
     * POST /api/v1/admin/invitation-templates/{templateId}/fields
     */
    public function store(StoreTemplateFieldRequest $request, $templateId): JsonResponse
    {
        // Verify template exists
        $template = InvitationTemplate::findOrFail($templateId);

        // Get next display order
        $maxOrder = TemplateField::where('template_id', $templateId)->max('display_order') ?? 0;

        $field = TemplateField::create([
            'template_id' => $templateId,
            'display_order' => $request->display_order ?? ($maxOrder + 1),
            ...$request->validated(),
        ]);

        // Mark template as having custom fields
        if (! $template->has_custom_fields) {
            $template->update(['has_custom_fields' => true]);
        }

        return response()->json([
            'message' => 'Field created successfully',
            'data' => $field->fresh(),
        ], 201);
    }

    /**
     * Display the specified field.
     *
     * GET /api/v1/admin/invitation-templates/{templateId}/fields/{fieldId}
     */
    public function show($templateId, $fieldId): JsonResponse
    {
        $field = TemplateField::where('template_id', $templateId)
            ->findOrFail($fieldId);

        return response()->json([
            'message' => 'Field retrieved successfully',
            'data' => $field,
        ]);
    }

    /**
     * Update the specified field.
     *
     * PUT /api/v1/admin/invitation-templates/{templateId}/fields/{fieldId}
     */
    public function update(UpdateTemplateFieldRequest $request, $templateId, $fieldId): JsonResponse
    {
        $field = TemplateField::where('template_id', $templateId)
            ->findOrFail($fieldId);

        $field->update($request->validated());

        return response()->json([
            'message' => 'Field updated successfully',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Remove the specified field.
     *
     * DELETE /api/v1/admin/invitation-templates/{templateId}/fields/{fieldId}
     */
    public function destroy($templateId, $fieldId): JsonResponse
    {
        $field = TemplateField::where('template_id', $templateId)
            ->findOrFail($fieldId);

        $field->delete();

        // Check if template still has fields
        $remainingFields = TemplateField::where('template_id', $templateId)->count();
        if ($remainingFields === 0) {
            InvitationTemplate::find($templateId)->update(['has_custom_fields' => false]);
        }

        return response()->json([
            'message' => 'Field deleted successfully',
        ]);
    }

    /**
     * Reorder fields by updating their display_order.
     *
     * POST /api/v1/admin/invitation-templates/{templateId}/fields/reorder
     */
    public function reorder(Request $request, $templateId): JsonResponse
    {
        $request->validate([
            'field_ids' => 'required|array',
            'field_ids.*' => 'integer|exists:template_fields,id',
        ]);

        // Verify all fields belong to this template
        $fields = TemplateField::where('template_id', $templateId)
            ->whereIn('id', $request->field_ids)
            ->get();

        if ($fields->count() !== count($request->field_ids)) {
            return response()->json([
                'message' => 'Some fields do not belong to this template',
            ], 422);
        }

        // Update display order
        foreach ($request->field_ids as $order => $fieldId) {
            TemplateField::where('id', $fieldId)
                ->where('template_id', $templateId)
                ->update(['display_order' => $order]);
        }

        return response()->json([
            'message' => 'Fields reordered successfully',
        ]);
    }

    /**
     * Toggle field active status.
     *
     * POST /api/v1/admin/invitation-templates/{templateId}/fields/{fieldId}/toggle-active
     */
    public function toggleActive($templateId, $fieldId): JsonResponse
    {
        $field = TemplateField::where('template_id', $templateId)
            ->findOrFail($fieldId);

        $field->update(['is_active' => ! $field->is_active]);

        return response()->json([
            'message' => $field->is_active ? 'Field activated' : 'Field deactivated',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Duplicate a field.
     *
     * POST /api/v1/admin/invitation-templates/{templateId}/fields/{fieldId}/duplicate
     */
    public function duplicate($templateId, $fieldId): JsonResponse
    {
        $originalField = TemplateField::where('template_id', $templateId)
            ->findOrFail($fieldId);

        // Get next display order
        $maxOrder = TemplateField::where('template_id', $templateId)->max('display_order') ?? 0;

        // Create duplicate with modified key
        $duplicate = $originalField->replicate();
        $duplicate->field_key = $originalField->field_key.'_copy_'.time();
        $duplicate->field_label = $originalField->field_label.' (Copy)';
        $duplicate->display_order = $maxOrder + 1;
        $duplicate->save();

        return response()->json([
            'message' => 'Field duplicated successfully',
            'data' => $duplicate,
        ], 201);
    }
}
