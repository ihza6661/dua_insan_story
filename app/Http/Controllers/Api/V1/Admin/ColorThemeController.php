<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ColorThemeController extends Controller
{
    /**
     * Get all color themes for a template.
     */
    public function index(int $templateId): JsonResponse
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
     * Add or update a color theme for a template.
     */
    public function store(Request $request, int $templateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'theme_key' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
            'name' => 'required|string|max:100',
            'primary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'secondary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'accent' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'background' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'text' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'textMuted' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = InvitationTemplate::findOrFail($templateId);
        $colorThemes = $template->color_themes ?? [];

        // Add or update the theme
        $colorThemes[$request->theme_key] = [
            'name' => $request->name,
            'primary' => $request->primary,
            'secondary' => $request->secondary,
            'accent' => $request->accent,
            'background' => $request->background,
            'text' => $request->text,
            'textMuted' => $request->textMuted,
        ];

        $template->color_themes = $colorThemes;
        $template->save();

        return response()->json([
            'message' => 'Color theme saved successfully',
            'data' => [
                'theme_key' => $request->theme_key,
                'theme' => $colorThemes[$request->theme_key],
            ],
        ]);
    }

    /**
     * Update an existing color theme.
     */
    public function update(Request $request, int $templateId, string $themeKey): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'primary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'secondary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'accent' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'background' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'text' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'textMuted' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = InvitationTemplate::findOrFail($templateId);
        $colorThemes = $template->color_themes ?? [];

        if (!isset($colorThemes[$themeKey])) {
            return response()->json([
                'message' => 'Color theme not found',
            ], 404);
        }

        $colorThemes[$themeKey] = [
            'name' => $request->name,
            'primary' => $request->primary,
            'secondary' => $request->secondary,
            'accent' => $request->accent,
            'background' => $request->background,
            'text' => $request->text,
            'textMuted' => $request->textMuted,
        ];

        $template->color_themes = $colorThemes;
        $template->save();

        return response()->json([
            'message' => 'Color theme updated successfully',
            'data' => [
                'theme_key' => $themeKey,
                'theme' => $colorThemes[$themeKey],
            ],
        ]);
    }

    /**
     * Delete a color theme.
     */
    public function destroy(int $templateId, string $themeKey): JsonResponse
    {
        $template = InvitationTemplate::findOrFail($templateId);
        $colorThemes = $template->color_themes ?? [];

        if (!isset($colorThemes[$themeKey])) {
            return response()->json([
                'message' => 'Color theme not found',
            ], 404);
        }

        // Prevent deleting the default theme
        if ($themeKey === 'default') {
            return response()->json([
                'message' => 'Cannot delete the default theme',
            ], 400);
        }

        unset($colorThemes[$themeKey]);
        $template->color_themes = $colorThemes;
        $template->save();

        return response()->json([
            'message' => 'Color theme deleted successfully',
        ]);
    }

    /**
     * Set default color themes for a new template.
     */
    public function setDefaults(int $templateId): JsonResponse
    {
        $template = InvitationTemplate::findOrFail($templateId);

        $defaultThemes = [
            'default' => [
                'name' => 'Elegant Gold',
                'primary' => '#b89968',
                'secondary' => '#3d2e28',
                'accent' => '#796656',
                'background' => '#faf9f8',
                'text' => '#3d2e28',
                'textMuted' => '#796656',
            ],
            'rose' => [
                'name' => 'Romantic Rose',
                'primary' => '#e91e63',
                'secondary' => '#880e4f',
                'accent' => '#c2185b',
                'background' => '#fce4ec',
                'text' => '#4a0e2e',
                'textMuted' => '#880e4f',
            ],
            'sage' => [
                'name' => 'Soft Sage',
                'primary' => '#8d9f87',
                'secondary' => '#4a5d4a',
                'accent' => '#6b7f65',
                'background' => '#f5f8f4',
                'text' => '#2d3a2d',
                'textMuted' => '#5a6b5a',
            ],
            'navy' => [
                'name' => 'Classic Navy',
                'primary' => '#1e3a5f',
                'secondary' => '#0d1f3c',
                'accent' => '#2c5282',
                'background' => '#f0f4f8',
                'text' => '#1a202c',
                'textMuted' => '#4a5568',
            ],
            'burgundy' => [
                'name' => 'Deep Burgundy',
                'primary' => '#7c2d37',
                'secondary' => '#4a1419',
                'accent' => '#9c3848',
                'background' => '#fdf5f5',
                'text' => '#2d1114',
                'textMuted' => '#5a2327',
            ],
        ];

        $template->color_themes = $defaultThemes;
        $template->save();

        return response()->json([
            'message' => 'Default color themes set successfully',
            'data' => [
                'color_themes' => $defaultThemes,
            ],
        ]);
    }
}
