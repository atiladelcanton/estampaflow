<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Models;

use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $schema_version_id
 * @property string $key
 * @property string $label
 * @property ServiceParameterFieldType $field_type
 * @property string|null $unit
 * @property bool $required
 * @property bool $affects_pricing
 * @property list<string>|null $options
 * @property array<string, mixed>|null $validation_rules
 * @property mixed $default_value
 * @property int $sort_order
 * @property bool $active
 */
final class ServiceParameterDefinition extends Model
{
    use BelongsToTenant;
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'schema_version_id',
        'key',
        'label',
        'field_type',
        'unit',
        'required',
        'affects_pricing',
        'options',
        'validation_rules',
        'default_value',
        'sort_order',
        'active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'field_type' => ServiceParameterFieldType::class,
            'required' => 'boolean',
            'affects_pricing' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
            'default_value' => 'json',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<ServiceTypeSchemaVersion, $this> */
    public function schemaVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeSchemaVersion::class, 'schema_version_id');
    }
}
