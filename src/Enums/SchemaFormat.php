<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Enums;

enum SchemaFormat: string
{
    case Email = 'email';
    case IdnEmail = 'idn-email';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'date-time';
    case Duration = 'duration';
    case Regex = 'regex';
    case Uri = 'uri';
    case UriReference = 'uri-reference';
    case UriTemplate = 'uri-template';
    case Uuid = 'uuid';
    case Hostname = 'hostname';
    case Ipv4 = 'ipv4';
    case Ipv6 = 'ipv6';
    case Iri = 'iri';
    case IriReference = 'iri-reference';
    case JsonPointer = 'json-pointer';
    case RelativeJsonPointer = 'relative-json-pointer';
}
