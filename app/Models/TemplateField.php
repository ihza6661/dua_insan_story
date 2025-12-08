<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TemplateField
 *
 * @property int $id
 * @property int $template_id
 * @property string $field_key
 * @property string $field_label
 * @property string $field_type
 * @property string $field_category
 * @property string|null $placeholder
 * @property string|null $default_value
 * @property array|null $validation_rules
 * @property string|null $help_text
 * @property int $display_order
 * @property bool $is_active
 */
class TemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'field_key',
        'field_label',
        'field_type',
        'field_category',
        'placeholder',
        'default_value',
        'validation_rules',
        'help_text',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the template that owns this field.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'template_id');
    }

    // ========== HELPER METHODS ==========

    /**
     * Get Laravel validation rules from JSON configuration.
     */
    public function getLaravelValidationRules(): array
    {
        $rules = [];
        $validationRules = $this->validation_rules ?? [];

        // Required check
        if ($validationRules['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-specific validations
        switch ($this->field_type) {
            case 'text':
            case 'textarea':
                $rules[] = 'string';
                if (isset($validationRules['max'])) {
                    $rules[] = 'max:'.$validationRules['max'];
                }
                if (isset($validationRules['min'])) {
                    $rules[] = 'min:'.$validationRules['min'];
                }
                break;

            case 'date':
                $rules[] = 'date';
                break;

            case 'time':
                $rules[] = 'date_format:H:i';
                break;

            case 'url':
                $rules[] = 'url';
                break;

            case 'email':
                $rules[] = 'email';
                break;

            case 'phone':
                $rules[] = 'string';
                $rules[] = 'regex:/^[+]?[0-9]{10,15}$/';
                break;

            case 'image':
                $rules[] = 'image';
                $rules[] = 'max:5120'; // 5MB
                break;

            case 'color':
                $rules[] = 'string';
                $rules[] = 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
                break;
        }

        // Custom pattern
        if (isset($validationRules['pattern'])) {
            $rules[] = 'regex:'.$validationRules['pattern'];
        }

        return $rules;
    }

    /**
     * Scope to get only active fields.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
