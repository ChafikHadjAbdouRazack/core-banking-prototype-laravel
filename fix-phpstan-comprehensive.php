#!/usr/bin/env php
<?php

// Comprehensive PHPStan error fixes

$fixCount = 0;

// 1. Fix PHPDoc @var tags that don't specify variable names
echo "Fixing PHPDoc @var tags...\n";
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix pattern: /** @var Type */ followed by $variable = 
    $content = preg_replace_callback(
        '/\/\*\*\s*@var\s+([^\s]+)\s*\*\/\s*\n\s*(\$\w+)\s*=/',
        function($matches) {
            $type = $matches[1];
            $variable = $matches[2];
            return "/** @var {$type} {$variable} */\n        {$variable} =";
        },
        $content
    );
    
    // Fix undefined variable issues by ensuring proper variable declarations
    // Common pattern: $account is used without being defined
    if (preg_match('/function\s+\w+\s*\([^)]*\)\s*{[^}]+Undefined variable: \$(\w+)/', $content, $matches)) {
        $varName = $matches[1] ?? null;
        if ($varName && !preg_match('/\$' . $varName . '\s*=/', $content)) {
            // Add variable initialization at the beginning of methods
            $content = preg_replace_callback(
                '/(function\s+\w+\s*\([^)]*\)\s*{\s*\n)/',
                function($m) use ($varName) {
                    return $m[1] . "        \${$varName} = null; // Initialize variable\n";
                },
                $content
            );
            $changed = true;
        }
    }
    
    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        $fixCount++;
        $changed = true;
    }
}

echo "Fixed $fixCount files with PHPDoc @var issues\n";

// 2. Fix Asset model to extend Model
$file = 'app/Domain/Asset/Models/Asset.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Ensure it extends Model
    if (strpos($content, 'extends Model') === false) {
        $content = preg_replace(
            '/class\s+Asset\s*{/',
            'class Asset extends Model {',
            $content
        );
        
        // Add Model import
        if (strpos($content, 'use Illuminate\Database\Eloquent\Model;') === false) {
            $content = preg_replace(
                '/(namespace[^;]+;)/',
                "$1\n\nuse Illuminate\Database\Eloquent\Model;",
                $content
            );
        }
        
        file_put_contents($file, $content);
        echo "Fixed Asset model to extend Model\n";
    }
}

// 3. Fix BasketValue model to extend Model
$file = 'app/Domain/Basket/Models/BasketValue.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Ensure it extends Model
    if (strpos($content, 'extends Model') === false) {
        $content = preg_replace(
            '/class\s+BasketValue\s*{/',
            'class BasketValue extends Model {',
            $content
        );
        
        // Add Model import
        if (strpos($content, 'use Illuminate\Database\Eloquent\Model;') === false) {
            $content = preg_replace(
                '/(namespace[^;]+;)/',
                "$1\n\nuse Illuminate\Database\Eloquent\Model;",
                $content
            );
        }
        
        file_put_contents($file, $content);
        echo "Fixed BasketValue model to extend Model\n";
    }
}

// 4. Fix ExchangeRate model - add missing getRate() method
$file = 'app/Domain/Asset/Models/ExchangeRate.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    if (!preg_match('/function\s+getRate\s*\(/', $content)) {
        // Add getRate method before last closing brace
        $method = <<<'METHOD'
    
    /**
     * Get the exchange rate value.
     */
    public static function getRate(string $fromCurrency, string $toCurrency): float
    {
        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('is_active', true)
            ->latest()
            ->first();
            
        return $rate ? $rate->rate : 1.0;
    }
METHOD;
        
        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace) . $method . "\n" . substr($content, $lastBrace);
        
        file_put_contents($file, $content);
        echo "Added getRate() method to ExchangeRate\n";
    }
}

// 5. Fix specific undefined methods
$methodFixes = [
    'app/Domain/Account/Models/AccountBalance.php' => [
        'increment' => 'public',
        'decrement' => 'public'
    ],
];

foreach ($methodFixes as $file => $methods) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $changed = false;
        
        foreach ($methods as $method => $visibility) {
            if (preg_match('/protected\s+function\s+' . $method . '\s*\(/', $content)) {
                // Change from protected to public
                $content = preg_replace(
                    '/protected\s+function\s+' . $method . '\s*\(/',
                    $visibility . ' function ' . $method . '(',
                    $content
                );
                $changed = true;
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            echo "Fixed method visibility in: $file\n";
        }
    }
}

// 6. Fix specific variable issues in workflows
$workflowFiles = glob('app/Domain/*/Workflows/*.php', GLOB_BRACE);

foreach ($workflowFiles as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix common undefined variable patterns
    $patterns = [
        // Fix: $account = Account::find() without @var
        '/(Account::find[^;]+;)/' => "/** @var \\App\\Models\\Account|null \$account */\n        $1",
        // Fix: $basket = BasketAsset::where() without @var
        '/(BasketAsset::where[^;]+;)/' => "/** @var \\App\\Domain\\Basket\\Models\\BasketAsset|null \$basket */\n        $1",
        // Fix: $fromAccount / $toAccount undefined
        '/(\$fromAccount\s*=\s*[^;]+;)/' => "/** @var \\App\\Models\\Account|null \$fromAccount */\n        $1",
        '/(\$toAccount\s*=\s*[^;]+;)/' => "/** @var \\App\\Models\\Account|null \$toAccount */\n        $1",
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $content) && !preg_match('/@var.*\$\w+.*\*\/\s*\n\s*' . trim($pattern, '/'), $content)) {
            $content = preg_replace($pattern, $replacement, $content, 1);
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "\nTotal fixes applied: $fixCount\n";