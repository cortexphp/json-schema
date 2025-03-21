<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use BackedEnum;
use ReflectionEnum;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

class EnumConverter implements Converter
{
    /**
     * @var \ReflectionEnum<\BackedEnum>
     */
    protected ReflectionEnum $reflection;

    /**
     * @param class-string<\BackedEnum> $enum
     */
    public function __construct(
        protected string $enum,
    ) {
        // @phpstan-ignore function.alreadyNarrowedType
        if (! is_subclass_of($this->enum, BackedEnum::class)) {
            throw new SchemaException('Enum must be a backed enum');
        }

        $this->reflection = new ReflectionEnum($this->enum);
    }

    public function convert(): ObjectSchema
    {
        $schema = new ObjectSchema();

        // Get the description from the doc parser
        $description = $this->getDocParser($this->reflection)?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $schema->description($description);
        }

        // Get the basename of the enum namespace
        $parts = explode('\\', $this->enum);
        $enumName = end($parts);

        /** @var non-empty-array<int, string|int> $values */
        $values = array_column($this->enum::cases(), 'value');

        // Determine the backing type
        return match ($this->reflection->getBackingType()?->getName()) {
            'string' => $schema->properties((new StringSchema($enumName))->enum($values)->required()),
            'int' => $schema->properties((new IntegerSchema($enumName))->enum($values)->required()),
            default => throw new SchemaException('Unsupported enum backing type. Only "int" or "string" are supported.'),
        };
    }

    /**
     * @param ReflectionEnum<\BackedEnum> $reflection
     */
    protected function getDocParser(ReflectionEnum $reflection): ?DocParser
    {
        if ($docComment = $reflection->getDocComment()) {
            return new DocParser($docComment);
        }

        return null;
    }
}
