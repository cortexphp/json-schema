<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

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
     * @var \ReflectionEnum<object>
     */
    protected ReflectionEnum $reflection;

    /**
     * @param class-string<\BackedEnum> $enum
     */
    public function __construct(
        protected string $enum,
    ) {
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

        // Get the possible values of the enum cases
        $values = array_map(fn($case) => $case->getValue()->value, $this->reflection->getCases());

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
