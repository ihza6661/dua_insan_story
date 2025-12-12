<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ColorThemeController extends Controller
{
    /**
     * Get available color themes for a template (public).
     */
    public function getTemplateThemes(int $templateId): JsonResponse
    {
        $template = InvitationTemplate::findOrFail($templateId);

        return response()->json([
            'message' => 'Color themes retrieved successfully',
            'data' => [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'color_themes' => $template->color_themes ?? [],
            ],
        ]);
    }

    /**
     * Apply a color theme to an invitation (auth required).
     */
    public function applyTheme(Request $request, int $invitationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme_key' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission',
            ], 404);
        }

        // Validate theme exists in template
        $colorThemes = $invitation->template->color_themes ?? [];
        if (!isset($colorThemes[$request->theme_key])) {
            return response()->json([
                'message' => 'Color theme not found in this template',
            ], 404);
        }

        // Update color scheme
        $data = $invitation->data;
        $data->color_scheme = $request->theme_key;
        $data->save();

        return response()->json([
            'message' => 'Color theme applied successfully',
            'data' => [
                'invitation_id' => $invitation->id,
                'theme_key' => $request->theme_key,
                'theme' => $colorThemes[$request->theme_key],
            ],
        ]);
    }

    /**
     * Get current theme for an invitation (auth required).
     */
    public function getCurrentTheme(Request $request, int $invitationId): JsonResponse
    {
        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission',
            ], 404);
        }

        $colorScheme = $invitation->data->color_scheme ?? 'default';
        $colorThemes = $invitation->template->color_themes ?? [];
        $currentTheme = $colorThemes[$colorScheme] ?? ($colorThemes['default'] ?? null);

        return response()->json([
            'message' => 'Current theme retrieved successfully',
            'data' => [
                'invitation_id' => $invitation->id,
                'theme_key' => $colorScheme,
                'theme' => $currentTheme,
                'available_themes' => $colorThemes,
            ],
        ]);
    }
}
