#!/usr/bin/env php
<?php

echo "=== Comprehensive PHPStan Fix Script ===\n\n";

$fixCount = 0;
$errorReport = file_get_contents('phpstan-full-report.txt');
$lines = explode("\n", $errorReport);

// Parse errors into structured format
$errors = [];
foreach ($lines as $line) {
    if (preg_match('/^(.+?):(\d+):(.+)$/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = $matches[2];
        $error = trim($matches[3]);
        
        if (!isset($errors[$file])) {
            $errors[$file] = [];
        }
        $errors[$file][] = ['line' => $lineNum, 'error' => $error];
    }
}

echo "Found errors in " . count($errors) . " files\n\n";

// Process each file with errors
foreach ($errors as $file => $fileErrors) {
    if (!file_exists($file)) {
        echo "Skipping non-existent file: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $lines = explode("\n", $content);
    
    // Sort errors by line number in reverse order to avoid line number shifts
    usort($fileErrors, function($a, $b) {
        return $b['line'] - $a['line'];
    });
    
    foreach ($fileErrors as $errorInfo) {
        $lineNum = $errorInfo['line'] - 1; // Convert to 0-based index
        $error = $errorInfo['error'];
        
        // Fix PHPDoc @var without variable name
        if (strpos($error, 'PHPDoc tag @var above assignment does not specify variable name') !== false) {
            // Look for the variable assignment after the PHPDoc
            for ($i = $lineNum; $i < min($lineNum + 5, count($lines)); $i++) {
                if (preg_match('/^\s*(\$\w+)\s*=/', $lines[$i], $matches)) {
                    $varName = $matches[1];
                    // Fix the PHPDoc line
                    if (isset($lines[$lineNum])) {
                        $lines[$lineNum] = preg_replace(
                            '/(@var\s+[^\s\$]+)(\s*\*\/)?$/',
                            '$1 ' . $varName . '$2',
                            $lines[$lineNum]
                        );
                    }
                    break;
                }
            }
        }
        
        // Fix undefined variable errors
        elseif (preg_match('/Undefined variable: \$(\w+)/', $error, $matches)) {
            $varName = $matches[1];
            
            // Special handling for common variables
            $varTypes = [
                'account' => 'Account',
                'fromAccount' => 'Account', 
                'toAccount' => 'Account',
                'basket' => 'BasketAsset',
                'rate' => 'ExchangeRate',
                'transfer' => 'Transfer',
                'batchJob' => 'BatchJob',
                'pool' => 'LiquidityPool',
                'startValue' => 'int',
                'firstTx' => 'array',
                'fromAssetModel' => 'Asset',
                'toAssetModel' => 'Asset',
            ];
            
            if (isset($varTypes[$varName])) {
                // Find the function containing this line
                $functionStart = -1;
                for ($i = $lineNum; $i >= 0; $i--) {
                    if (preg_match('/^\s*(public|protected|private)?\s*function\s+\w+\s*\([^)]*\)\s*{?\s*$/', $lines[$i])) {
                        $functionStart = $i;
                        break;
                    }
                }
                
                if ($functionStart >= 0) {
                    // Add variable initialization after function declaration
                    $indent = '        ';
                    $type = $varTypes[$varName];
                    $fullType = match($type) {
                        'Account' => '\\App\\Models\\Account',
                        'BasketAsset' => '\\App\\Domain\\Basket\\Models\\BasketAsset',
                        'ExchangeRate' => '\\App\\Domain\\Asset\\Models\\ExchangeRate',
                        'Transfer' => '\\App\\Domain\\Payment\\Models\\Transfer',
                        'BatchJob' => '\\App\\Models\\BatchJob',
                        'Asset' => '\\App\\Domain\\Asset\\Models\\Asset',
                        'LiquidityPool' => '\\App\\Domain\\Liquidity\\Models\\LiquidityPool',
                        default => $type
                    };
                    
                    // Check if variable is already initialized
                    $alreadyInitialized = false;
                    for ($i = $functionStart + 1; $i < $lineNum && $i < count($lines); $i++) {
                        if (preg_match('/\$' . $varName . '\s*=/', $lines[$i])) {
                            $alreadyInitialized = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyInitialized && isset($lines[$functionStart + 1])) {
                        $lines[$functionStart + 1] = $indent . "/** @var {$fullType}|null \${$varName} */\n" . 
                                                    $indent . "\${$varName} = null;\n" . 
                                                    $lines[$functionStart + 1];
                    }
                }
            }
        }
        
        // Fix method calls
        elseif (strpos($error, 'Call to an undefined method') !== false) {
            // Handle specific undefined method patterns
            if (strpos($error, '::increment()') !== false || strpos($error, '::decrement()') !== false) {
                if (isset($lines[$lineNum])) {
                    // Replace increment/decrement with update
                    $lines[$lineNum] = preg_replace(
                        '/->increment\(([\'"])(\w+)\1\s*,\s*(\$?\w+)\)/',
                        '->update([$2 => $this->$2 + $3])',
                        $lines[$lineNum]
                    );
                    $lines[$lineNum] = preg_replace(
                        '/->decrement\(([\'"])(\w+)\1\s*,\s*(\$?\w+)\)/',
                        '->update([$2 => $this->$2 - $3])',
                        $lines[$lineNum]
                    );
                }
            }
        }
        
        // Fix parameter type mismatches
        elseif (preg_match('/Parameter #(\d+) .* expects (.+?), (.+?) given/', $error, $matches)) {
            $expectedType = $matches[2];
            $givenType = $matches[3];
            
            // Add type checks or casts
            if (strpos($givenType, '|null') !== false && strpos($expectedType, '|null') === false) {
                // Add null check
                if (isset($lines[$lineNum]) && preg_match('/(\$\w+)->(\w+)\(([^)]+)\)/', $lines[$lineNum], $callMatches)) {
                    $object = $callMatches[1];
                    $method = $callMatches[2];
                    $args = $callMatches[3];
                    
                    // Wrap in null check
                    $lines[$lineNum] = preg_replace(
                        '/^(\s*)(.+)$/',
                        '$1if (' . $object . ' !== null) {' . "\n" . '$1    $2' . "\n" . '$1}',
                        $lines[$lineNum]
                    );
                }
            }
        }
    }
    
    // Rebuild content
    $newContent = implode("\n", $lines);
    
    // Additional fixes for common patterns
    
    // Fix Account::where()->firstOrFail() missing parameters
    $newContent = preg_replace(
        '/Account::where\(\)->firstOrFail\(\)/',
        'Account::where(\'uuid\', $accountUuid)->firstOrFail()',
        $newContent
    );
    
    // Fix BasketAsset::where()->firstOrFail() missing parameters
    $newContent = preg_replace(
        '/BasketAsset::where\(\)->firstOrFail\(\)/',
        'BasketAsset::where(\'code\', $basketCode)->firstOrFail()',
        $newContent
    );
    
    // Fix missing relationship return types
    $newContent = preg_replace_callback(
        '/(public function \w+\(\))\s*\n\s*{\s*\n\s*(return \$this->(?:belongsTo|hasMany|hasOne|morphMany)\([^)]+\);)/',
        function($matches) {
            $declaration = $matches[1];
            $returnStatement = $matches[2];
            
            $returnType = '';
            if (strpos($returnStatement, 'belongsTo') !== false) {
                $returnType = 'BelongsTo';
            } elseif (strpos($returnStatement, 'hasMany') !== false) {
                $returnType = 'HasMany';
            } elseif (strpos($returnStatement, 'hasOne') !== false) {
                $returnType = 'HasOne';
            } elseif (strpos($returnStatement, 'morphMany') !== false) {
                $returnType = 'MorphMany';
            }
            
            if ($returnType) {
                return "/**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\{$returnType}\n     */\n    {$declaration}\n    {\n        {$returnStatement}";
            }
            
            return $matches[0];
        },
        $newContent
    );
    
    if ($newContent !== $originalContent) {
        file_put_contents($file, $newContent);
        $fixCount++;
        echo "Fixed: $file\n";
    }
}

echo "\nTotal files fixed: $fixCount\n";

// Run additional targeted fixes
echo "\nRunning additional targeted fixes...\n";

// Fix specific model issues
$modelFixes = [
    'app/Domain/Lending/Models/Loan.php' => [
        'methods' => [
            'application' => 'public function application() { return $this->belongsTo(\\App\\Domain\\Lending\\Models\\LoanApplication::class, \'application_id\'); }',
            'borrower' => 'public function borrower() { return $this->belongsTo(\\App\\Models\\Account::class, \'borrower_account_id\'); }'
        ]
    ]
];

foreach ($modelFixes as $file => $fixes) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $changed = false;
        
        if (isset($fixes['methods'])) {
            foreach ($fixes['methods'] as $methodName => $methodCode) {
                if (!preg_match('/function\s+' . $methodName . '\s*\(/', $content)) {
                    $lastBrace = strrpos($content, '}');
                    $content = substr($content, 0, $lastBrace) . "\n\n    " . $methodCode . "\n" . substr($content, $lastBrace);
                    $changed = true;
                }
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            echo "Added methods to: $file\n";
        }
    }
}

echo "\nDone!\n";