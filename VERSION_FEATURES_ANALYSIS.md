# JSON Schema Version Features Analysis

## Overview
This document tracks all JSON Schema features across different concerns and schema types, identifying which features need version validation and appropriate keyword handling.

## Feature Categories by Draft Version

### Draft 07 Features (Available in all supported versions)
- ✅ **Conditionals**: `if`, `then`, `else` (complete if-then-else construct)
- ✅ **Basic metadata**: `title`, `description`, `default`, `examples`
- ✅ **Read/Write**: `readOnly`, `writeOnly`
- ✅ **Content**: `contentMediaType`, `contentEncoding`
- ✅ **Format**: Basic format validation
- ✅ **Validation**: All basic validation keywords
- ✅ **Properties**: All object property keywords
- ✅ **Items**: All array item keywords including `contains`

### Draft 2019-09 Features
- ✅ **Schema structure**: `$anchor`, `$defs` (renamed from `definitions`), `$vocabulary`
- ✅ **Advanced validation**: `unevaluatedItems`, `unevaluatedProperties`
- ✅ **Dependencies**: `dependentRequired`, `dependentSchemas` (split from `dependencies`)
- ✅ **Contains**: `minContains`, `maxContains`
- ✅ **Content**: `contentSchema`
- ✅ **Metadata**: `deprecated`
- ✅ **Formats**: `duration`, `uuid`
- ✅ **Recursive**: `$recursiveAnchor`, `$recursiveRef` (replaced in 2020-12)

### Draft 2020-12 Features
- ✅ **Dynamic references**: `$dynamicAnchor`, `$dynamicRef` (replaces recursive keywords)
- ✅ **Array/Tuple**: `prefixItems` (new array/tuple syntax)
- ✅ **Format vocabularies**: Split into `format-annotation` and `format-assertion`
- ✅ **Vocabulary**: `unevaluated-vocabulary` (separate vocabulary)
- ✅ **Regex**: Unicode support in regular expressions
- ✅ **Schema bundling**: Compound schema documents

## Concerns Analysis

### ✅ IMPLEMENTED: HasConditionals
**Status**: ✅ Complete with version validation
- **Features**: `if`, `then`, `else`, `if-then-else`
- **Validation**: All features validated on method calls and during output
- **Detection**: Properly detects composite `IfThenElse` feature

### ✅ IMPLEMENTED: HasDefinitions
**Status**: ✅ Complete with version-aware keywords
- **Features**: `$defs` (2019-09+) vs `definitions` (Draft 07)
- **Validation**: Uses version-appropriate keywords automatically
- **Detection**: Reports `Defs` feature when using newer keyword

### ✅ IMPLEMENTED: HasMetadata
**Status**: ✅ Complete with version validation
- **Features**:
  - `default`, `examples` - Available in all versions ✅
  - `deprecated` - Draft 2019-09+ ✅ VALIDATION ADDED
  - `$comment` - Available in all versions ✅
- **Implementation**: Added validation in `deprecated()` method and feature detection

### ✅ IMPLEMENTED: HasReadWrite
**Status**: ✅ Complete with feature detection
- **Features**: `readOnly`, `writeOnly` - Available in Draft 07+ ✅
- **Implementation**: Added feature detection method (no validation needed - available in all versions)

### ✅ IMPLEMENTED: ArraySchema (contains features)
**Status**: ✅ Complete with validation
- **Features**:
  - `contains` - Available in Draft 07+ ✅
  - `minContains`, `maxContains` - Draft 2019-09+ ✅ VALIDATION ADDED
- **Implementation**: Added validation in `minContains()` and `maxContains()` methods, plus feature detection

### ✅ IMPLEMENTED: HasFormat
**Status**: ✅ Complete with version-specific validation
- **Features**:
  - Basic `format` - Available in all versions ✅
  - `duration`, `uuid` formats - Draft 2019-09+ ✅ VALIDATION ADDED
  - Format vocabularies - Draft 2020-12+ (not implemented - no current usage)
- **Implementation**: Added validation for version-specific formats in `format()` method and feature detection

### ✅ NO ACTION NEEDED: Basic Concerns
These concerns use features available in all supported versions:
- **HasTitle**: `title` - Available in all versions
- **HasDescription**: `description` - Available in all versions
- **HasEnum**: `enum` - Available in all versions
- **HasConst**: `const` - Available in all versions
- **HasValidation**: Basic validation keywords - Available in all versions
- **HasRef**: `$ref` - Available in all versions
- **HasRequired**: `required` - Available in all versions
- **HasProperties**: Object properties - Available in all versions
- **HasItems**: Basic array items - Available in all versions
- **HasNumericConstraints**: Numeric validation - Available in all versions

## Implementation Plan

### ✅ Phase 1: Critical Features (COMPLETED)
1. **✅ HasMetadata - `deprecated` validation**
   - ✅ Added validation in `deprecated()` method
   - ✅ Added feature detection method
   - ✅ Integrated with AbstractSchema feature collection

2. **✅ ArraySchema - contains count validation**
   - ✅ Added validation in `minContains()` and `maxContains()` methods
   - ✅ Added feature detection method
   - ✅ Integrated with feature validation system

3. **✅ HasReadWrite - feature detection**
   - ✅ Added feature detection method for completeness
   - ✅ Integrated with AbstractSchema feature collection

### ✅ Phase 2: Format Features (COMPLETED)
3. **✅ HasFormat - format-specific validation**
   - ✅ Added validation for version-specific formats (`duration`, `uuid`)
   - ✅ Added format feature detection
   - ✅ Integrated with feature validation system
   - Note: Format vocabularies not implemented (no current usage in codebase)

### Phase 3: Advanced Features (Future)
4. **Unevaluated properties/items** (if implemented)
5. **Dependent schemas/required** (if implemented)
6. **PrefixItems** (if array/tuple redesign implemented)

## Testing Strategy

✅ **COMPLETED** - All implemented features have comprehensive test coverage:
1. **✅ Method validation tests**: Features are validated when methods are called
2. **✅ Output validation tests**: Features are validated during `toArray()`
3. **✅ Version-appropriate output tests**: Correct keywords are used (`$defs` vs `definitions`)
4. **✅ Error message tests**: Helpful error messages for unsupported features
5. **✅ Feature detection tests**: Features are properly detected and reported

**Current Test Results**: 145 tests passing with 931 assertions

## Notes

- All features should follow the same pattern as `HasConditionals` and `HasDefinitions`
- Use `validateFeatureSupport()` in method calls for immediate feedback
- Use `getXxxFeatures()` methods for feature detection during output
- Use `addFeatureIfSupported()` for conditional feature addition
- Maintain backward compatibility - no breaking changes to existing APIs
