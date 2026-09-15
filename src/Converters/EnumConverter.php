<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use ReflectionEnum;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithEnums;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithDocblocks;

class EnumConverter implements Converter
{
    use InteractsWithEnums;
    use InteractsWithDocblocks;

    /**
     * @var \ReflectionEnum<\UnitEnum>
     */
    protected ReflectionEnum $reflection;

    protected SchemaVersion $version;

    /**
     * @param class-string<\UnitEnum> $enum
     */
    public function __construct(
        protected string $enum,
        ?SchemaVersion $schemaVersion = null,
    ) {
        $this->reflection = new ReflectionEnum($this->enum);
        $this->version = $schemaVersion ?? SchemaVersion::default();

        if (! $this->reflection->isBacked()) {
            throw new SchemaException('Enum must be a backed enum');
        }
    }

    public function convert(): StringSchema|IntegerSchema
    {
        $enumName = $this->reflection->getShortName();

        // Determine the backing type
        $schema = match ($this->reflection->getBackingType()?->getName()) {
            'string' => new StringSchema($enumName, $this->version),
            'int' => new IntegerSchema($enumName, $this->version),
            default => throw new SchemaException(
                'Unsupported enum backing type. Only "int" or "string" are supported.',
            ),
        };

        $values = $this->backedEnumValues($this->enum);

        if ($values === null) {
            throw new SchemaException('Enum must be a backed enum');
        }

        $schema->enum($values);

        $this->applySchemaDocblock($schema, $this->docParser($this->reflection));

        return $schema;
    }
}
