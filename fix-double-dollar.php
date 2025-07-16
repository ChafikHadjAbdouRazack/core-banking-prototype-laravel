#!/usr/bin/env php
<?php

// Fix double dollar sign issues in the codebase

$fixCount = 0;
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Fix double dollar signs in variable declarations
    $content = preg_replace('/\$\$(\w+)/', '$$$1', $content);
    
    // Fix @var annotations with double dollar signs
    $content = preg_replace('/@var\s+([^\s]+)\s+\$\$(\w+)/', '@var $1 $$2', $content);
    
    // Fix specific patterns where Account::where() is missing parameters
    $content = preg_replace(
        '/Account::where\(\)->firstOrFail\(\)/',
        'Account::where(\'uuid\', $accountUuid)->firstOrFail()',
        $content
    );
    
    // Fix BasketAsset::where() missing parameters
    $content = preg_replace(
        '/BasketAsset::where\(\)->firstOrFail\(\)/',
        'BasketAsset::where(\'code\', $basketCode)->firstOrFail()',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
        echo "Fixed double dollar signs in: $file\n";
    }
}

echo "\nTotal files fixed: $fixCount\n";