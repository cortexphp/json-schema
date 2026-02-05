<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Enums;

/**
 * Represents features available in different JSON Schema specification versions.
 */
enum SchemaFeature: string
{
    // Draft 06 features
    case ContentMediaType = 'contentMediaType';
    case ContentEncoding = 'contentEncoding';

    // Draft 07 features
    case If = 'if';
    case Then = 'then';
    case Else = 'else';
    case IfThenElse = 'if-then-else';
    case WriteOnly = 'writeOnly';
    case ReadOnly = 'readOnly';
    case Comment = '$comment';
    case FormatDate = 'format-date';
    case FormatTime = 'format-time';
    case FormatRegex = 'format-regex';
    case FormatRelativeJsonPointer = 'format-relative-json-pointer';
    case FormatIdnEmail = 'format-idn-email';
    case FormatIdnHostname = 'format-idn-hostname';
    case FormatIri = 'format-iri';
    case FormatIriReference = 'format-iri-reference';

    // Draft 2019-09 new features
    case Anchor = '$anchor';
    case Defs = '$defs';
    case RecursiveAnchor = '$recursiveAnchor';
    case RecursiveRef = '$recursiveRef';
    case Vocabulary = '$vocabulary';
    case UnevaluatedItems = 'unevaluatedItems';
    case UnevaluatedProperties = 'unevaluatedProperties';
    case DependentRequired = 'dependentRequired';
    case DependentSchemas = 'dependentSchemas';
    case MaxContains = 'maxContains';
    case MinContains = 'minContains';
    case ContentSchema = 'contentSchema';
    case Deprecated = 'deprecated';
    case FormatDuration = 'format-duration';
    case FormatUuid = 'format-uuid';

    // Draft 2020-12 features
    case DynamicAnchor = '$dynamicAnchor';
    case DynamicRef = '$dynamicRef';
    case PrefixItems = 'prefixItems';
    case FormatAnnotation = 'format-annotation';
    case FormatAssertion = 'format-assertion';
    case UnevaluatedVocabulary = 'unevaluated-vocabulary';
    case UnicodeRegex = 'unicode-regex';
    case CompoundSchemaDocuments = 'compound-schema-documents';

    // Legacy support checks (for backward compatibility)
    case RecursiveRefLegacy = 'recursiveRef';
    case RecursiveAnchorLegacy = 'recursiveAnchor';
    case DynamicRefLegacy = 'dynamicRef';
    case DynamicAnchorLegacy = 'dynamicAnchor';

    /**
     * Get the minimum schema version that supports this feature.
     */
    public function getMinimumVersion(): SchemaVersion
    {
        return match ($this) {
            // Draft 06 features
            self::ContentMediaType,
            self::ContentEncoding => SchemaVersion::Draft_06,

            // Draft 07 features
            self::If,
            self::Then,
            self::Else,
            self::IfThenElse,
            self::WriteOnly,
            self::ReadOnly,
            self::Comment,
            self::FormatDate,
            self::FormatTime,
            self::FormatRegex,
            self::FormatRelativeJsonPointer,
            self::FormatIdnEmail,
            self::FormatIdnHostname,
            self::FormatIri,
            self::FormatIriReference => SchemaVersion::Draft_07,

            // Draft 2019-09 features
            self::Anchor,
            self::Defs,
            self::RecursiveAnchor,
            self::RecursiveRef,
            self::Vocabulary,
            self::UnevaluatedItems,
            self::UnevaluatedProperties,
            self::DependentRequired,
            self::DependentSchemas,
            self::MaxContains,
            self::MinContains,
            self::ContentSchema,
            self::Deprecated,
            self::FormatDuration,
            self::FormatUuid => SchemaVersion::Draft_2019_09,

            // Draft 2020-12 features
            self::DynamicAnchor,
            self::DynamicRef,
            self::PrefixItems,
            self::FormatAnnotation,
            self::FormatAssertion,
            self::UnevaluatedVocabulary,
            self::UnicodeRegex,
            self::CompoundSchemaDocuments,
            self::DynamicRefLegacy,
            self::DynamicAnchorLegacy => SchemaVersion::Draft_2020_12,

            // Legacy features (2019-09 only)
            self::RecursiveRefLegacy,
            self::RecursiveAnchorLegacy => SchemaVersion::Draft_2019_09,
        };
    }

    /**
     * Get the maximum schema version that supports this feature.
     * Returns null if the feature is supported in all versions from minimum onwards.
     */
    public function getMaximumVersion(): ?SchemaVersion
    {
        return match ($this) {
            // Features only available in 2019-09 (replaced in 2020-12)
            self::RecursiveAnchor,
            self::RecursiveRef,
            self::RecursiveRefLegacy,
            self::RecursiveAnchorLegacy => SchemaVersion::Draft_2019_09,

            // All other features continue to be supported
            default => null,
        };
    }

    /**
     * Get a human-readable description of this feature.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::If => 'Conditional schema validation with if keyword',
            self::Then => 'Conditional schema validation with then keyword',
            self::Else => 'Conditional schema validation with else keyword',
            self::IfThenElse => 'Complete if-then-else conditional validation',
            self::ContentMediaType => 'Content media type annotation',
            self::ContentEncoding => 'Content encoding annotation',
            self::WriteOnly => 'Write-only property annotation',
            self::ReadOnly => 'Read-only property annotation',
            self::Comment => 'Schema annotation comment',
            self::FormatDate => 'RFC 3339 full-date format validation',
            self::FormatTime => 'RFC 3339 time format validation',
            self::FormatRegex => 'Regular expression format validation',
            self::FormatRelativeJsonPointer => 'Relative JSON Pointer format validation',
            self::FormatIdnEmail => 'Internationalized email format validation',
            self::FormatIdnHostname => 'Internationalized hostname format validation',
            self::FormatIri => 'Internationalized URI format validation',
            self::FormatIriReference => 'Internationalized URI reference format validation',

            self::Anchor => 'Plain name anchors for schema identification',
            self::Defs => 'Schema definitions (renamed from definitions)',
            self::RecursiveAnchor => 'Recursive anchor for extending recursive schemas',
            self::RecursiveRef => 'Recursive reference for extending recursive schemas',
            self::Vocabulary => 'Vocabulary declarations in meta-schemas',
            self::UnevaluatedItems => 'Unevaluated items validation',
            self::UnevaluatedProperties => 'Unevaluated properties validation',
            self::DependentRequired => 'Dependent required properties (split from dependencies)',
            self::DependentSchemas => 'Dependent schemas (split from dependencies)',
            self::MaxContains => 'Maximum number of contains matches',
            self::MinContains => 'Minimum number of contains matches',
            self::ContentSchema => 'Schema for decoded content',
            self::Deprecated => 'Property deprecation annotation',
            self::FormatDuration => 'ISO 8601 duration format validation',
            self::FormatUuid => 'RFC 4122 UUID format validation',

            self::DynamicAnchor => 'Dynamic anchor for cross-schema references',
            self::DynamicRef => 'Dynamic reference for cross-schema references',
            self::PrefixItems => 'Prefix items for tuple validation',
            self::FormatAnnotation => 'Format as annotation vocabulary',
            self::FormatAssertion => 'Format as assertion vocabulary',
            self::UnevaluatedVocabulary => 'Separate vocabulary for unevaluated keywords',
            self::UnicodeRegex => 'Unicode support in regular expressions',
            self::CompoundSchemaDocuments => 'Embedded schemas and bundling support',

            self::RecursiveRefLegacy => 'Legacy recursive reference (2019-09 only)',
            self::RecursiveAnchorLegacy => 'Legacy recursive anchor (2019-09 only)',
            self::DynamicRefLegacy => 'Dynamic reference (legacy alias)',
            self::DynamicAnchorLegacy => 'Dynamic anchor (legacy alias)',
        };
    }

    /**
     * Check if this feature was introduced in the given version.
     */
    public function wasIntroducedIn(SchemaVersion $schemaVersion): bool
    {
        return $this->getMinimumVersion() === $schemaVersion;
    }

    /**
     * Check if this feature was deprecated/removed in the given version.
     */
    public function wasRemovedIn(SchemaVersion $schemaVersion): bool
    {
        $maxVersion = $this->getMaximumVersion();

        if (! $maxVersion instanceof SchemaVersion) {
            return false;
        }

        // Feature was removed if the version is after the maximum supported version
        return $schemaVersion->getYear() > $maxVersion->getYear();
    }
}
