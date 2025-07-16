#!/usr/bin/env php
<?php

echo "=== Precise PHPStan Fix Script ===\n\n";

// First, let's analyze the current errors
exec('./vendor/bin/phpstan analyse --error-format=raw 2>&1', $output);

$errors = [];
foreach ($output as $line) {
    if (preg_match('/^(.+?):(\d+):(.+)$/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = $matches[2];
        $error = trim($matches[3]);
        
        if (!isset($errors[$file])) {
            $errors[$file] = [];
        }
        $errors[$file][$lineNum] = $error;
    }
}

echo "Found errors in " . count($errors) . " files\n\n";

$fixCount = 0;

// 1. Fix PHPDoc @var tags without variable names
foreach ($errors as $file => $fileErrors) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $changed = false;
    
    foreach ($fileErrors as $lineNum => $error) {
        if (strpos($error, 'PHPDoc tag @var above assignment does not specify variable name') !== false) {
            $lineIndex = $lineNum - 1;
            
            // Find the variable on the next line
            for ($i = $lineIndex + 1; $i < min($lineIndex + 5, count($lines)); $i++) {
                if (preg_match('/^\s*(\$\w+)\s*=/', $lines[$i], $matches)) {
                    $varName = $matches[1];
                    
                    // Fix the PHPDoc line
                    if (preg_match('/^\s*\*\s*@var\s+([^\s\$]+)\s*\*\/?\s*$/', $lines[$lineIndex], $docMatches)) {
                        $lines[$lineIndex] = str_replace('@var ' . $docMatches[1], '@var ' . $docMatches[1] . ' ' . $varName, $lines[$lineIndex]);
                        $changed = true;
                    }
                    break;
                }
            }
        }
    }
    
    if ($changed) {
        file_put_contents($file, implode("\n", $lines));
        echo "Fixed PHPDoc in: $file\n";
        $fixCount++;
    }
}

// 2. Fix undefined variables
$variableFiles = [
    'app/Domain/Account/Workflows/CreateAccountActivity.php',
    'app/Domain/Asset/Workflows/Activities/CalculateExchangeRateActivity.php',
    'app/Domain/Asset/Workflows/Activities/CompleteAssetTransferActivity.php',
    'app/Domain/Asset/Workflows/Activities/OptimizedInitiateAssetTransferActivity.php',
    'app/Domain/Banking/Workflows/ConnectBankAccountActivity.php',
    'app/Domain/Basket/Activities/CalculateBasketValueActivity.php',
    'app/Domain/Basket/Activities/UpdateBasketComponentsActivity.php',
    'app/Domain/Basket/Workflows/ProcessBasketTransactionActivity.php',
    'app/Domain/Liquidity/Workflows/Activities/AddLiquidityActivity.php',
    'app/Domain/Liquidity/Workflows/Activities/ProcessLiquidityRebalanceActivity.php',
    'app/Domain/Payment/Activities/CreditAccountActivity.php',
    'app/Domain/Payment/Activities/DebitAccountActivity.php',
    'app/Domain/Payment/Workflows/TransferActivity.php',
];

foreach ($variableFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix common undefined variables
    $patterns = [
        // Account variables
        '/(\$account\s*=\s*Account::)/' => "/** @var \\App\\Models\\Account|null \$account */\n        $1",
        '/(\$fromAccount\s*=\s*Account::)/' => "/** @var \\App\\Models\\Account|null \$fromAccount */\n        $1",
        '/(\$toAccount\s*=\s*Account::)/' => "/** @var \\App\\Models\\Account|null \$toAccount */\n        $1",
        
        // Basket variables
        '/(\$basket\s*=\s*BasketAsset::)/' => "/** @var \\App\\Domain\\Basket\\Models\\BasketAsset|null \$basket */\n        $1",
        
        // Asset variables
        '/(\$fromAssetModel\s*=\s*Asset::)/' => "/** @var \\App\\Domain\\Asset\\Models\\Asset|null \$fromAssetModel */\n        $1",
        '/(\$toAssetModel\s*=\s*Asset::)/' => "/** @var \\App\\Domain\\Asset\\Models\\Asset|null \$toAssetModel */\n        $1",
        
        // Other variables
        '/(\$rate\s*=\s*ExchangeRate::)/' => "/** @var \\App\\Domain\\Asset\\Models\\ExchangeRate|null \$rate */\n        $1",
        '/(\$transfer\s*=\s*Transfer::)/' => "/** @var \\App\\Domain\\Payment\\Models\\Transfer|null \$transfer */\n        $1",
        '/(\$pool\s*=\s*LiquidityPool::)/' => "/** @var \\App\\Domain\\Liquidity\\Models\\LiquidityPool|null \$pool */\n        $1",
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $content) && !preg_match('/@var.*' . preg_quote(explode(' ', $pattern)[0], '/') . '/', $content)) {
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
    }
    
    // Fix missing where clauses
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
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed variables in: $file\n";
        $fixCount++;
    }
}

// 3. Fix relationship return type annotations
$modelFiles = glob('app/Domain/*/Models/*.php');
$modelFiles = array_merge($modelFiles, glob('app/Models/*.php'));

foreach ($modelFiles as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Add return type annotations for relationships
    $content = preg_replace_callback(
        '/(\/\*\*[^*]*\*\/\s*)?(public function \w+\(\))\s*\n\s*{\s*\n\s*(return \$this->(belongsTo|hasMany|hasOne|morphMany|morphTo|belongsToMany)\([^;]+;)/',
        function($matches) {
            $docBlock = $matches[1] ?? '';
            $declaration = $matches[2];
            $returnStatement = $matches[3];
            $relationType = $matches[4];
            
            // Check if already has @return
            if (strpos($docBlock, '@return') !== false) {
                return $matches[0];
            }
            
            $returnType = match($relationType) {
                'belongsTo' => 'BelongsTo',
                'hasMany' => 'HasMany',
                'hasOne' => 'HasOne',
                'morphMany' => 'MorphMany',
                'morphTo' => 'MorphTo',
                'belongsToMany' => 'BelongsToMany',
                default => ''
            };
            
            if ($returnType) {
                $newDocBlock = "/**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\{$returnType}\n     */\n    ";
                return $newDocBlock . $declaration . "\n    {\n        " . $returnStatement;
            }
            
            return $matches[0];
        },
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed relationships in: $file\n";
        $fixCount++;
    }
}

// 4. Fix specific class issues
$specificFixes = [
    // ApiKey verify method
    'app/Models/ApiKey.php' => function($content) {
        // Ensure proper type hint for verify method
        $content = preg_replace(
            '/(public static function verify\(string \$plainKey\): \?ApiKey\s*\{[^}]+\$apiKey = self::where[^;]+->first\(\);)/',
            '$1' . "\n        /** @var ApiKey|null \$apiKey */",
            $content
        );
        return $content;
    },
    
    // Fix increment/decrement calls
    'app/Domain/Account/Models/AccountBalance.php' => function($content) {
        // Make increment/decrement public if they're protected
        $content = str_replace('protected function increment', 'public function increment', $content);
        $content = str_replace('protected function decrement', 'public function decrement', $content);
        return $content;
    },
];

foreach ($specificFixes as $file => $fixer) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $newContent = $fixer($content);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Applied specific fixes to: $file\n";
        $fixCount++;
    }
}

echo "\nTotal fixes applied: $fixCount\n";
echo "\nRunning PHPStan to check results...\n";

// Check results
exec('./vendor/bin/phpstan analyse 2>&1 | tail -5', $result);
foreach ($result as $line) {
    echo $line . "\n";
}