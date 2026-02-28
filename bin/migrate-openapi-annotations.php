#!/usr/bin/env php
<?php

/**
 * Migrate OpenAPI docblock annotations (@OA\...) to PHP 8 attributes (#[OA\...]).
 *
 * Usage:
 *   php bin/migrate-openapi-annotations.php [path] [--dry-run]
 *
 * Examples:
 *   php bin/migrate-openapi-annotations.php app/Http/Controllers/Api/Rewards/RewardsController.php
 *   php bin/migrate-openapi-annotations.php app/Http/Controllers/Api/Documentation/ --dry-run
 *   php bin/migrate-openapi-annotations.php app/Http/Controllers/ --dry-run
 */

declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv, true);
$path = $argv[1] ?? null;

if (! $path) {
    echo "Usage: php bin/migrate-openapi-annotations.php <path> [--dry-run]\n";
    exit(1);
}

$basePath = dirname(__DIR__);
$fullPath = str_starts_with($path, '/') ? $path : $basePath . '/' . $path;

if (is_file($fullPath)) {
    $files = [$fullPath];
} elseif (is_dir($fullPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $files = [];
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
} else {
    echo "Error: Path '{$fullPath}' not found.\n";
    exit(1);
}

$totalFiles = 0;
$totalConverted = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (! str_contains($content, '@OA\\')) {
        continue;
    }

    $totalFiles++;
    $result = convertFile($content);

    if ($result === $content) {
        echo "SKIP (no changes): " . relativePath($file, $basePath) . "\n";
        continue;
    }

    $totalConverted++;
    $rel = relativePath($file, $basePath);

    if ($dryRun) {
        echo "WOULD CONVERT: {$rel}\n";
    } else {
        file_put_contents($file, $result);
        echo "CONVERTED: {$rel}\n";
    }
}

echo "\n" . ($dryRun ? '[DRY RUN] ' : '') . "Processed {$totalFiles} files, converted {$totalConverted}.\n";

// ---------------------------------------------------------------------------
// Conversion logic
// ---------------------------------------------------------------------------

function convertFile(string $content): string
{
    // Ensure OA import exists
    $content = ensureOAImport($content);

    // Strategy: find each docblock that contains @OA\, extract the OA annotations,
    // leave non-OA doc content in the docblock, and place OA as #[...] attributes.
    $content = convertDocblockAnnotations($content);

    return $content;
}

function ensureOAImport(string $content): string
{
    // If file already has the attribute import, skip
    if (preg_match('/use\s+OpenApi\\\\Attributes\b/', $content)) {
        return $content;
    }

    // Find the last use statement and add after it
    if (preg_match('/^(use\s+[^;]+;)\s*\n/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
        // Find the position of the last use statement
        $lastUsePos = 0;
        $lastUseEnd = 0;
        if (preg_match_all('/^use\s+[^;]+;\s*\n/m', $content, $allMatches, PREG_OFFSET_CAPTURE)) {
            $lastMatch = end($allMatches[0]);
            $lastUsePos = $lastMatch[1];
            $lastUseEnd = $lastUsePos + strlen($lastMatch[0]);
        }

        $import = "use OpenApi\\Attributes as OA;\n";
        // Don't double-add
        if (! str_contains($content, $import)) {
            $content = substr($content, 0, $lastUseEnd) . $import . substr($content, $lastUseEnd);
        }
    }

    return $content;
}

function convertDocblockAnnotations(string $content): string
{
    // Match docblocks containing @OA\
    // We process from bottom to top to preserve offsets
    $pattern = '/\/\*\*\s*\n(.*?)\*\//s';

    $allMatches = [];
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[0] as $i => $match) {
        $docblock = $match[0];
        $offset = $match[1];

        if (! str_contains($docblock, '@OA\\')) {
            continue;
        }

        $allMatches[] = [
            'docblock' => $docblock,
            'offset'   => $offset,
            'length'   => strlen($docblock),
        ];
    }

    // Process from bottom to top
    $allMatches = array_reverse($allMatches);

    foreach ($allMatches as $matchInfo) {
        $docblock = $matchInfo['docblock'];
        $offset = $matchInfo['offset'];
        $length = $matchInfo['length'];

        // Extract OA annotation content from the docblock
        $oaBlocks = extractOABlocks($docblock);
        $nonOaDocblock = removeOAFromDocblock($docblock);

        // Convert each OA block to PHP 8 attribute syntax
        $attributes = [];
        foreach ($oaBlocks as $oaBlock) {
            $attr = convertOABlockToAttribute($oaBlock);
            if ($attr !== null) {
                $attributes[] = $attr;
            }
        }

        // Determine indentation from the line where the docblock starts
        $indent = getIndentation($content, $offset);

        // Build replacement
        $replacement = '';

        // Keep non-OA docblock if it has content
        if (hasDocContent($nonOaDocblock)) {
            $replacement .= $nonOaDocblock . "\n";
        }

        // Add attributes
        foreach ($attributes as $attr) {
            $replacement .= indentAttribute($attr, $indent) . "\n";
        }

        // Remove trailing newline from replacement since we want to place it before the next element
        $replacement = rtrim($replacement, "\n");

        // Replace in content
        $content = substr($content, 0, $offset) . $replacement . substr($content, $offset + $length);
    }

    return $content;
}

function extractOABlocks(string $docblock): array
{
    // Remove the /** and */ delimiters and * prefixes
    $lines = explode("\n", $docblock);
    $cleanContent = '';

    foreach ($lines as $line) {
        // Remove leading whitespace, *, and reconstruct
        $stripped = preg_replace('/^\s*\*?\s?/', '', $line);
        if ($stripped === null) {
            continue;
        }
        // Skip opening /** and closing */
        if (trim($stripped) === '/**' || trim($stripped) === '/' || trim($stripped) === '*/') {
            continue;
        }
        $cleanContent .= $stripped . "\n";
    }

    // Find top-level @OA\ blocks with balanced parentheses
    $blocks = [];
    $pos = 0;
    $len = strlen($cleanContent);

    while ($pos < $len) {
        $oaStart = strpos($cleanContent, '@OA\\', $pos);
        if ($oaStart === false) {
            break;
        }

        // Find the opening parenthesis
        $parenStart = strpos($cleanContent, '(', $oaStart);
        if ($parenStart === false) {
            $pos = $oaStart + 4;
            continue;
        }

        // Find balanced closing parenthesis
        $depth = 0;
        $end = $parenStart;
        for ($i = $parenStart; $i < $len; $i++) {
            if ($cleanContent[$i] === '(') {
                $depth++;
            } elseif ($cleanContent[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        $block = substr($cleanContent, $oaStart, $end - $oaStart + 1);
        $blocks[] = trim($block);
        $pos = $end + 1;
    }

    return $blocks;
}

function removeOAFromDocblock(string $docblock): string
{
    $lines = explode("\n", $docblock);
    $resultLines = [];
    $inOA = false;
    $parenDepth = 0;

    foreach ($lines as $line) {
        $stripped = trim(preg_replace('/^\s*\*?\s?/', '', $line) ?? '');

        if (! $inOA && preg_match('/@OA\\\\/', $stripped)) {
            $inOA = true;
            $parenDepth = 0;
            // Count parens in this line
            $parenDepth += substr_count($stripped, '(') - substr_count($stripped, ')');
            if ($parenDepth <= 0) {
                $inOA = false;
            }
            continue;
        }

        if ($inOA) {
            $parenDepth += substr_count($stripped, '(') - substr_count($stripped, ')');
            if ($parenDepth <= 0) {
                $inOA = false;
            }
            continue;
        }

        $resultLines[] = $line;
    }

    return implode("\n", $resultLines);
}

function hasDocContent(string $docblock): bool
{
    // Check if the remaining docblock has any meaningful content
    $cleaned = preg_replace('/\/\*\*|\*\/|\*/', '', $docblock);
    $cleaned = trim($cleaned ?? '');

    return $cleaned !== '' && $cleaned !== '/';
}

function convertOABlockToAttribute(string $oaBlock): ?string
{
    // Remove @OA\ prefix → OA\
    $attr = preg_replace('/^@OA\\\\/', 'OA\\', $oaBlock);
    if ($attr === null) {
        return null;
    }

    // Convert nested @OA\ to new OA\
    $attr = str_replace('@OA\\', 'new OA\\', $attr);

    // Convert annotation-style params to named PHP params
    $attr = convertParamsToNamed($attr);

    return '#[' . $attr . ']';
}

function convertParamsToNamed(string $attr): string
{
    // This is the complex part. We need to convert:
    //   OA\Get(path="/api/...", operationId="...", ...)
    // to:
    //   OA\Get(path: '/api/...', operationId: '...', ...)

    // Strategy: Parse character by character, track context
    $result = '';
    $i = 0;
    $len = strlen($attr);

    while ($i < $len) {
        // Look for parameter = value patterns at the appropriate nesting level
        // Match: identifier="value" or identifier=value or identifier={...}

        // Check if we're at a parameter assignment (word=)
        if (isAtParamAssignment($attr, $i)) {
            // Extract param name
            $nameStart = $i;
            $nameEnd = strpos($attr, '=', $i);
            if ($nameEnd === false) {
                $result .= $attr[$i];
                $i++;
                continue;
            }
            $paramName = trim(substr($attr, $nameStart, $nameEnd - $nameStart));
            $result .= $paramName . ': ';
            $i = $nameEnd + 1;

            // Now handle the value
            if ($i < $len && $attr[$i] === '"') {
                // Quoted string value
                $strEnd = findClosingQuote($attr, $i);
                $strValue = substr($attr, $i + 1, $strEnd - $i - 1);
                // Convert to single quotes (escape single quotes in value)
                $strValue = str_replace("'", "\\'", $strValue);
                $result .= "'" . $strValue . "'";
                $i = $strEnd + 1;
            } elseif ($i < $len && $attr[$i] === '{') {
                // Curly braces → square brackets (JSON array/object syntax)
                $braceContent = extractBalanced($attr, $i, '{', '}');
                $converted = convertBracesToBrackets($braceContent);
                $result .= $converted;
                $i += strlen($braceContent);
            } else {
                // Other value (number, boolean, new OA\..., etc.)
                // Just pass through until comma, closing paren, or newline
                $result .= '';
                // Let the main loop handle it
            }
            continue;
        }

        // Convert double-quoted strings that aren't part of param assignments
        if ($attr[$i] === '"' && ! isPartOfParamValue($attr, $i)) {
            $strEnd = findClosingQuote($attr, $i);
            $strValue = substr($attr, $i + 1, $strEnd - $i - 1);
            $strValue = str_replace("'", "\\'", $strValue);
            $result .= "'" . $strValue . "'";
            $i = $strEnd + 1;
            continue;
        }

        $result .= $attr[$i];
        $i++;
    }

    return $result;
}

function isAtParamAssignment(string $str, int $pos): bool
{
    // Check if we're at the start of: identifier=
    // Must be after ( or , or whitespace
    if ($pos > 0) {
        $prevNonSpace = $pos - 1;
        while ($prevNonSpace >= 0 && ctype_space($str[$prevNonSpace])) {
            $prevNonSpace--;
        }
        if ($prevNonSpace >= 0 && ! in_array($str[$prevNonSpace], ['(', ',', "\n"])) {
            return false;
        }
    }

    // Look ahead for identifier=
    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)=/', substr($str, $pos), $m)) {
        // Make sure it's not part of => or == or inside a string
        $afterEqual = $pos + strlen($m[1]) + 1;
        if ($afterEqual < strlen($str) && ($str[$afterEqual] === '=' || $str[$afterEqual] === '>')) {
            return false;
        }
        return true;
    }

    return false;
}

function findClosingQuote(string $str, int $openPos): int
{
    $i = $openPos + 1;
    $len = strlen($str);
    while ($i < $len) {
        if ($str[$i] === '\\') {
            $i += 2;
            continue;
        }
        if ($str[$i] === '"') {
            return $i;
        }
        $i++;
    }
    return $len - 1;
}

function isPartOfParamValue(string $str, int $pos): bool
{
    // Check if this quote is right after an =
    $prev = $pos - 1;
    while ($prev >= 0 && ctype_space($str[$prev])) {
        $prev--;
    }
    return $prev >= 0 && $str[$prev] === '=';
}

function extractBalanced(string $str, int $start, string $open, string $close): string
{
    $depth = 0;
    $i = $start;
    $len = strlen($str);

    while ($i < $len) {
        if ($str[$i] === $open) {
            $depth++;
        } elseif ($str[$i] === $close) {
            $depth--;
            if ($depth === 0) {
                return substr($str, $start, $i - $start + 1);
            }
        } elseif ($str[$i] === '"') {
            $i = findClosingQuote($str, $i);
        }
        $i++;
    }

    return substr($str, $start);
}

function convertBracesToBrackets(string $content): string
{
    // Simple replacement: { → [, } → ]
    // Handle nested structures
    $result = '';
    $len = strlen($content);

    for ($i = 0; $i < $len; $i++) {
        if ($content[$i] === '{') {
            $result .= '[';
        } elseif ($content[$i] === '}') {
            $result .= ']';
        } elseif ($content[$i] === '"') {
            // Pass through strings, converting to single quotes
            $end = findClosingQuote($content, $i);
            $str = substr($content, $i + 1, $end - $i - 1);
            $str = str_replace("'", "\\'", $str);
            $result .= "'" . $str . "'";
            $i = $end;
        } else {
            $result .= $content[$i];
        }
    }

    return $result;
}

function getIndentation(string $content, int $offset): string
{
    // Find the start of the line containing the offset
    $lineStart = strrpos($content, "\n", $offset - strlen($content));
    if ($lineStart === false) {
        $lineStart = 0;
    } else {
        $lineStart++;
    }

    $line = substr($content, $lineStart, $offset - $lineStart);
    preg_match('/^(\s*)/', $line, $m);

    return $m[1] ?? '';
}

function indentAttribute(string $attribute, string $indent): string
{
    $lines = explode("\n", $attribute);
    $result = [];
    foreach ($lines as $i => $line) {
        if ($i === 0) {
            $result[] = $indent . $line;
        } else {
            $result[] = $indent . '    ' . trim($line);
        }
    }

    return implode("\n", $result);
}

function relativePath(string $path, string $base): string
{
    return str_replace($base . '/', '', $path);
}
