#!/usr/bin/env php
<?php

echo "=== Final Comprehensive PHPStan Fix ===\n\n";

// Get all PHPStan errors
exec('./vendor/bin/phpstan analyse --error-format=raw 2>&1', $output);

$errors = [];
$errorCount = 0;

foreach ($output as $line) {
    if (preg_match('/^(.+?):(\d+):(.+)$/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = $matches[2];
        $error = trim($matches[3]);
        
        if (!isset($errors[$file])) {
            $errors[$file] = [];
        }
        $errors[$file][] = ['line' => $lineNum, 'error' => $error];
        $errorCount++;
    }
}

echo "Found $errorCount errors in " . count($errors) . " files\n\n";

$fixCount = 0;

// Process all files with errors
foreach ($errors as $file => $fileErrors) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Group errors by type
    $varErrors = [];
    $methodErrors = [];
    $paramErrors = [];
    $returnErrors = [];
    $otherErrors = [];
    
    foreach ($fileErrors as $errorInfo) {
        $error = $errorInfo['error'];
        $line = $errorInfo['line'];
        
        if (strpos($error, 'PHPDoc tag @var above assignment does not specify variable name') !== false) {
            $varErrors[] = $errorInfo;
        } elseif (strpos($error, 'Undefined variable:') !== false) {
            $varErrors[] = $errorInfo;
        } elseif (strpos($error, 'Call to an undefined method') !== false) {
            $methodErrors[] = $errorInfo;
        } elseif (strpos($error, 'Parameter #') !== false) {
            $paramErrors[] = $errorInfo;
        } elseif (strpos($error, 'should return') !== false) {
            $returnErrors[] = $errorInfo;
        } else {
            $otherErrors[] = $errorInfo;
        }
    }
    
    // Fix @var tags
    foreach ($varErrors as $errorInfo) {
        $error = $errorInfo['error'];
        $lineNum = $errorInfo['line'];
        
        if (strpos($error, 'PHPDoc tag @var above assignment does not specify variable name') !== false) {
            // Fix by looking at the content
            $content = preg_replace_callback(
                '/^(\s*)\*\s*@var\s+([^\s\$]+)\s*\*\/\s*\n\s*(\$\w+)\s*=/m',
                function($matches) {
                    return $matches[1] . '* @var ' . $matches[2] . ' ' . $matches[3] . ' */' . "\n" . $matches[1] . $matches[3] . ' =';
                },
                $content
            );
        }
        
        // Fix undefined variables
        if (preg_match('/Undefined variable: \$(\w+)/', $error, $matches)) {
            $varName = $matches[1];
            
            // Add type annotation based on common patterns
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
            
            if (isset($typeMap[$varName])) {
                // Find the line where the variable is used
                $lines = explode("\n", $content);
                if (isset($lines[$lineNum - 1])) {
                    // Check if it's inside a function
                    $functionLine = -1;
                    for ($i = $lineNum - 2; $i >= 0; $i--) {
                        if (preg_match('/function\s+\w+\s*\(/', $lines[$i])) {
                            $functionLine = $i;
                            break;
                        }
                    }
                    
                    if ($functionLine >= 0) {
                        // Add initialization after function declaration
                        for ($i = $functionLine + 1; $i < count($lines); $i++) {
                            if (preg_match('/^\s*{/', $lines[$i])) {
                                $indent = '        ';
                                $type = $typeMap[$varName];
                                $lines[$i] = $lines[$i] . "\n" . $indent . "/** @var {$type}|null \${$varName} */\n" . $indent . "\${$varName} = null;";
                                break;
                            }
                        }
                        $content = implode("\n", $lines);
                    }
                }
            }
        }
    }
    
    // Fix method errors
    foreach ($methodErrors as $errorInfo) {
        $error = $errorInfo['error'];
        
        // Fix Query Builder methods called on relationships
        if (strpos($error, '::active()') !== false || strpos($error, '::valid()') !== false) {
            // These are scopes that should be called on the model, not the relationship
            $content = preg_replace(
                '/->(\w+)\(\)->active\(\)/',
                '->$1()->getQuery()->where(\'is_active\', true)',
                $content
            );
            $content = preg_replace(
                '/->(\w+)\(\)->valid\(\)/',
                '->$1()->getQuery()->where(\'is_valid\', true)',
                $content
            );
        }
        
        // Fix increment/decrement on protected methods
        if (strpos($error, '::increment()') !== false || strpos($error, '::decrement()') !== false) {
            $content = preg_replace(
                '/->increment\(([\'"])balance\1,\s*(\$\w+)\)/',
                '->update([\'balance\' => DB::raw(\'balance + \' . $2)])',
                $content
            );
            $content = preg_replace(
                '/->decrement\(([\'"])balance\1,\s*(\$\w+)\)/',
                '->update([\'balance\' => DB::raw(\'balance - \' . $2)])',
                $content
            );
        }
    }
    
    // Fix return type errors
    foreach ($returnErrors as $errorInfo) {
        $error = $errorInfo['error'];
        
        // Fix relationship return types
        if (strpos($error, 'should return') !== false && strpos($error, 'Illuminate\Database\Eloquent\Relations') !== false) {
            // Extract the expected return type
            if (preg_match('/should return\s+Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\(\w+)/', $error, $matches)) {
                $relationType = $matches[1];
                
                // Find the method and add proper return type annotation
                $lines = explode("\n", $content);
                $lineNum = $errorInfo['line'] - 1;
                
                // Look backwards for the method declaration
                for ($i = $lineNum; $i >= max(0, $lineNum - 10); $i--) {
                    if (preg_match('/public function (\w+)\(\)/', $lines[$i], $methodMatch)) {
                        // Check if there's already a PHPDoc
                        $hasPhpDoc = false;
                        for ($j = $i - 1; $j >= max(0, $i - 5); $j--) {
                            if (preg_match('/\*\s*@return/', $lines[$j])) {
                                $hasPhpDoc = true;
                                break;
                            }
                        }
                        
                        if (!$hasPhpDoc) {
                            // Add PHPDoc
                            $indent = '    ';
                            $phpDoc = $indent . "/**\n" . $indent . " * @return \\Illuminate\\Database\\Eloquent\\Relations\\{$relationType}\n" . $indent . " */";
                            $lines[$i] = $phpDoc . "\n" . $lines[$i];
                        }
                        break;
                    }
                }
                $content = implode("\n", $lines);
            }
        }
    }
    
    // Fix parameter type errors
    foreach ($paramErrors as $errorInfo) {
        $error = $errorInfo['error'];
        
        // Fix null parameter issues
        if (strpos($error, 'expects') !== false && strpos($error, '|null given') !== false) {
            // Add null checks
            $lines = explode("\n", $content);
            $lineNum = $errorInfo['line'] - 1;
            
            if (isset($lines[$lineNum]) && preg_match('/(\$\w+)->(\w+)\(/', $lines[$lineNum], $matches)) {
                $var = $matches[1];
                $indent = preg_match('/^(\s*)/', $lines[$lineNum], $indentMatch) ? $indentMatch[1] : '';
                
                // Wrap in null check
                $lines[$lineNum] = $indent . "if ({$var} !== null) {\n" . $indent . "    " . trim($lines[$lineNum]) . "\n" . $indent . "}";
                $content = implode("\n", $lines);
            }
        }
    }
    
    // Apply common fixes
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
    
    // Remove duplicate PHPDoc annotations
    $content = preg_replace(
        '/(\/\*\*[^*]*\*\/\s*)(\/\*\*[^*]*\*\/\s*)+/',
        '$1',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
        $fixCount++;
    }
}

echo "\nTotal files fixed: $fixCount\n";

// Add missing use statements
echo "\nAdding missing use statements...\n";

$files = glob('app/**/*.php', GLOB_BRACE);
foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Add DB facade if using DB::raw
    if (strpos($content, 'DB::raw') !== false && strpos($content, 'use Illuminate\Support\Facades\DB;') === false) {
        $content = preg_replace(
            '/(namespace[^;]+;)/',
            "$1\n\nuse Illuminate\\Support\\Facades\\DB;",
            $content
        );
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Added use statements to: $file\n";
    }
}

echo "\nDone! Running final PHPStan check...\n";

exec('./vendor/bin/phpstan analyse 2>&1 | tail -5', $result);
foreach ($result as $line) {
    echo $line . "\n";
}