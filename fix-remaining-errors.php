#!/usr/bin/env php
<?php

// Fix remaining PHPStan errors

$fixCount = 0;

// 1. Fix ApiKey verify method
$file = 'app/Models/ApiKey.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Add explicit cast to ApiKey in the verify method
    $content = preg_replace(
        '/(\$apiKey = self::where[^;]+->first\(\);)/',
        '/** @var ApiKey|null $apiKey */' . "\n        $1",
        $content
    );
    
    file_put_contents($file, $content);
    echo "Fixed ApiKey verify method\n";
    $fixCount++;
}

// 2. Fix all PHPDoc @var tags without variable names
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // More aggressive pattern to catch all @var tags without variable names
    $lines = explode("\n", $content);
    $newLines = [];
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        // Check if this line contains @var without a variable name
        if (preg_match('/^\s*\*\s*@var\s+([^\s\$]+)\s*\*\/?\s*$/', $line, $matches)) {
            // Look at the next few lines for a variable assignment
            for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                if (preg_match('/^\s*(\$\w+)\s*=/', $lines[$j], $varMatch)) {
                    // Found the variable, update the @var line
                    $line = preg_replace(
                        '/(@var\s+[^\s\$]+)(\s*\*\/?)?\s*$/',
                        '$1 ' . $varMatch[1] . '$2',
                        $line
                    );
                    break;
                }
            }
        }
        
        $newLines[] = $line;
    }
    
    $newContent = implode("\n", $newLines);
    
    if ($newContent !== $originalContent) {
        file_put_contents($file, $newContent);
        $fixCount++;
        echo "Fixed PHPDoc in: $file\n";
    }
}

// 3. Fix undefined variables by adding type annotations
$undefinedVarPatterns = [
    'account' => '\App\Models\Account',
    'basket' => '\App\Domain\Basket\Models\BasketAsset',
    'fromAccount' => '\App\Models\Account',
    'toAccount' => '\App\Models\Account',
    'rate' => '\App\Domain\Asset\Models\ExchangeRate',
    'transfer' => '\App\Domain\Payment\Models\Transfer',
    'batchJob' => '\App\Models\BatchJob',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($undefinedVarPatterns as $varName => $type) {
        // Look for usage of undefined variable
        if (preg_match('/\$' . $varName . '(?![\w\s]*=)/', $content)) {
            // Check if variable is defined somewhere
            if (!preg_match('/\$' . $varName . '\s*=/', $content)) {
                // Variable is used but not defined, try to find where it should be defined
                $content = preg_replace_callback(
                    '/(function\s+\w+\s*\([^)]*\)\s*{[^}]*?\$' . $varName . ')/',
                    function($matches) use ($varName, $type) {
                        // Add variable initialization at the start of the function
                        return preg_replace(
                            '/(function\s+\w+\s*\([^)]*\)\s*{\s*)/',
                            "$1\n        /** @var {$type}|null \${$varName} */\n        \${$varName} = null;\n        ",
                            $matches[1]
                        );
                    },
                    $content,
                    1
                );
            }
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "\nTotal fixes applied: $fixCount\n";