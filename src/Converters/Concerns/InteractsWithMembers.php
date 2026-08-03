<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters\Concerns;

use ReflectionProperty;
use ReflectionParameter;
use Cortex\JsonSchema\Contracts\JsonSchema;

trait InteractsWithMembers
{
    /**
     * Build the shared base schema for a reflection property or parameter.
     */
    protected function baseMemberSchema(ReflectionProperty|ReflectionParameter $member): JsonSchema
    {
        $type = $member->getType();
        $jsonSchema = $this->getSchemaFromReflectionType($type);
        $jsonSchema->title($member->getName());

        if ($type === null || $type->allowsNull()) {
            $jsonSchema->nullable();
        }

        $this->applyBackedEnumValues($jsonSchema, $type);

        return $jsonSchema;
    }
}
