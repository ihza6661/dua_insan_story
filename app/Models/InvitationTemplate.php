<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class InvitationTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $thumbnail_image
 * @property float $price
 * @property string $template_component
 * @property bool $is_active
 * @property int $usage_count
 */
class InvitationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail_image',
        'price',
        'template_component',
        'is_active',
        'usage_count',
        'has_custom_fields',
        'color_themes',
        'preview_data',
        'export_settings',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_active' => 'boolean',
            'usage_count' => 'integer',
            'has_custom_fields' => 'boolean',
            'color_themes' => 'array',
            'preview_data' => 'array',
            'export_settings' => 'array',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get all products using this template.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'template_id');
    }

    /**
     * Get all digital invitations using this template.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(DigitalInvitation::class, 'template_id');
    }

    /**
     * Get all custom fields for this template.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(TemplateField::class, 'template_id');
    }

    // ========== HELPER METHODS ==========

    /**
     * Increment usage count when a customer purchases this template.
     */
    public function incrementUsageCount(): void
    {
        $this->increment('usage_count');
    }
}
