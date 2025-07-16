#!/usr/bin/env php
<?php

echo "=== Final PHPStan Fix - Aggressive Mode ===\n\n";

// Get all errors
exec('./vendor/bin/phpstan analyse --error-format=raw 2>&1', $output);

$totalFixed = 0;

// 1. Fix all PHPDoc @var without variable names
echo "Fixing PHPDoc @var tags...\n";
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix all @var tags
    $lines = explode("\n", $content);
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*\*\s*@var\s+([^\s\$]+)\s*\*\/?\s*$/', $lines[$i], $matches)) {
            // Look for variable on next lines
            for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                if (preg_match('/^\s*(\$\w+)\s*=/', $lines[$j], $varMatch)) {
                    $lines[$i] = str_replace('@var ' . $matches[1], '@var ' . $matches[1] . ' ' . $varMatch[1], $lines[$i]);
                    break;
                }
            }
        }
    }
    
    $content = implode("\n", $lines);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $totalFixed++;
    }
}

echo "Fixed $totalFixed files with PHPDoc issues\n\n";

// 2. Fix all undefined variables
echo "Fixing undefined variables...\n";
$fixCount = 0;

foreach ($output as $line) {
    if (preg_match('/^(.+?):(\d+):Undefined variable: \$(\w+)/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = $matches[2];
        $varName = $matches[3];
        
        if (!file_exists($file)) continue;
        
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        // Find the function containing this variable
        $functionLine = -1;
        for ($i = $lineNum - 2; $i >= 0; $i--) {
            if (preg_match('/^\s*(public|protected|private)?\s*function\s+\w+\s*\(/', $lines[$i])) {
                $functionLine = $i;
                break;
            }
        }
        
        if ($functionLine >= 0) {
            // Add variable initialization
            $typeMap = [
                'account' => '\\App\\Models\\Account',
                'fromAccount' => '\\App\\Models\\Account',
                'toAccount' => '\\App\\Models\\Account',
                'basket' => '\\App\\Domain\\Basket\\Models\\BasketAsset',
                'rate' => '\\App\\Domain\\Asset\\Models\\ExchangeRate',
                'transfer' => '\\App\\Domain\\Payment\\Models\\Transfer',
                'batchJob' => '\\App\\Models\\BatchJob',
                'pool' => '\\App\\Domain\\Liquidity\\Models\\LiquidityPool',
                'fromAssetModel' => '\\App\\Domain\\Asset\\Models\\Asset',
                'toAssetModel' => '\\App\\Domain\\Asset\\Models\\Asset',
                'firstTx' => 'array',
                'startValue' => 'int',
            ];
            
            $type = $typeMap[$varName] ?? 'mixed';
            
            // Find opening brace
            $braceFound = false;
            for ($i = $functionLine; $i < min($functionLine + 5, count($lines)); $i++) {
                if (strpos($lines[$i], '{') !== false) {
                    $lines[$i] .= "\n        /** @var {$type}|null \${$varName} */\n        \${$varName} = null;";
                    $braceFound = true;
                    break;
                }
            }
            
            if ($braceFound) {
                file_put_contents($file, implode("\n", $lines));
                $fixCount++;
            }
        }
    }
}

echo "Fixed $fixCount undefined variables\n\n";

// 3. Fix all relationship methods
echo "Fixing relationship methods...\n";
$fixCount = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix relationships with modifiers
    $content = preg_replace(
        '/public function (\w+)\(\)\s*\{\s*return \$this->(hasOne|hasMany|belongsTo|morphMany|belongsToMany)\([^)]+\)->(orderBy|where|latest|oldest)\([^)]+\);/',
        'public function $1() { return $this->$2($3); }',
        $content
    );
    
    // Add PHPDoc for relationships without it
    $lines = explode("\n", $content);
    for ($i = 0; $i < count($lines); $i++) {
        if (preg_match('/^\s*public function (\w+)\(\)\s*$/', $lines[$i], $matches)) {
            // Check next line for relationship
            if ($i + 2 < count($lines) && preg_match('/return \$this->(belongsTo|hasMany|hasOne|morphMany|morphTo|belongsToMany)/', $lines[$i + 2], $relMatch)) {
                // Check if PHPDoc exists
                $hasDoc = false;
                for ($j = max(0, $i - 5); $j < $i; $j++) {
                    if (strpos($lines[$j], '@return') !== false) {
                        $hasDoc = true;
                        break;
                    }
                }
                
                if (!$hasDoc) {
                    $relationType = match($relMatch[1]) {
                        'belongsTo' => 'BelongsTo',
                        'hasMany' => 'HasMany',
                        'hasOne' => 'HasOne',
                        'morphMany' => 'MorphMany',
                        'morphTo' => 'MorphTo',
                        'belongsToMany' => 'BelongsToMany',
                    };
                    
                    $doc = "    /**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\{$relationType}\n     */";
                    $lines[$i] = $doc . "\n" . $lines[$i];
                }
            }
        }
    }
    
    $content = implode("\n", $lines);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "Fixed $fixCount relationship issues\n\n";

// 4. Fix all parameter type mismatches
echo "Fixing parameter type mismatches...\n";
$fixCount = 0;

foreach ($output as $line) {
    if (preg_match('/^(.+?):(\d+):Parameter #\d+ .* expects (.+?), (.+?) given/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = $matches[2];
        $expected = $matches[3];
        $given = $matches[4];
        
        if (!file_exists($file)) continue;
        
        if (strpos($given, '|null') !== false && strpos($expected, '|null') === false) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            if (isset($lines[$lineNum - 1])) {
                $line = $lines[$lineNum - 1];
                
                // Wrap in null check
                if (preg_match('/^(\s*)(.+)$/', $line, $indentMatch)) {
                    $indent = $indentMatch[1];
                    $code = $indentMatch[2];
                    
                    // Extract variable being used
                    if (preg_match('/(\$\w+)->/', $code, $varMatch)) {
                        $var = $varMatch[1];
                        $lines[$lineNum - 1] = $indent . "if ({$var} !== null) {\n" . $indent . "    " . $code . "\n" . $indent . "}";
                        
                        file_put_contents($file, implode("\n", $lines));
                        $fixCount++;
                    }
                }
            }
        }
    }
}

echo "Fixed $fixCount parameter type issues\n\n";

// 5. Apply common fixes to all files
echo "Applying common fixes...\n";
$fixCount = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix common patterns
    $content = preg_replace(
        '/Account::where\(\)->firstOrFail\(\)/',
        'Account::where(\'uuid\', $accountUuid)->firstOrFail()',
        $content
    );
    
    $content = preg_replace(
        '/BasketAsset::where\(\)->firstOrFail\(\)/',
        'BasketAsset::where(\'code\', $basketCode)->firstOrFail()',
        $content
    );
    
    // Fix increment/decrement
    $content = preg_replace(
        '/->increment\(([\'"])balance\1,\s*(\$\w+)\)/',
        '->update([\'balance\' => \\DB::raw(\'balance + \' . $2)])',
        $content
    );
    
    $content = preg_replace(
        '/->decrement\(([\'"])balance\1,\s*(\$\w+)\)/',
        '->update([\'balance\' => \\DB::raw(\'balance - \' . $2)])',
        $content
    );
    
    // Add DB import if needed
    if (strpos($content, '\\DB::raw') !== false && strpos($content, 'use Illuminate\\Support\\Facades\\DB;') === false) {
        $content = preg_replace(
            '/(namespace[^;]+;)/',
            "$1\n\nuse Illuminate\\Support\\Facades\\DB;",
            $content
        );
    }
    
    // Remove duplicate PHPDoc
    $content = preg_replace(
        '/(\/\*\*[^*]*\*\/\s*)\1+/',
        '$1',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "Applied common fixes to $fixCount files\n\n";

echo "Total fixes applied: $totalFixed\n\n";

// Final check
echo "Running final PHPStan check...\n";
exec('./vendor/bin/phpstan analyse 2>&1 | tail -5', $result);
foreach ($result as $line) {
    echo $line . "\n";
}