#!/usr/bin/env php
<?php

// Final push to fix remaining PHPStan errors

$fixCount = 0;

// 1. Fix OpenBankingWithdrawalController
$file = 'app/Http/Controllers/OpenBankingWithdrawalController.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Add type assertion for account
    $content = preg_replace(
        '/(\$account = \$withdrawal->account;)/',
        "$1\n        if (!\$account instanceof \\App\\Models\\Account) {\n            return redirect()->route('dashboard')->with('error', 'Invalid account');\n        }",
        $content
    );
    
    file_put_contents($file, $content);
    $fixCount++;
    echo "Fixed: $file\n";
}

// 2. Fix ApiKey verify method
$file = 'app/Models/ApiKey.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Ensure the verify method properly returns the ApiKey
    $content = preg_replace(
        '/(if \(\$apiKey && Hash::check\(\$plainKey, \$apiKey->key_hash\)\) \{)\s*\n\s*(return \$apiKey;)\s*\n\s*(\})/',
        "$1\n            \$apiKey->recordUsage(request()->ip() ?? '127.0.0.1');\n            return \$apiKey;\n        $3",
        $content
    );
    
    file_put_contents($file, $content);
    $fixCount++;
    echo "Fixed ApiKey verify: $file\n";
}

// 3. Fix missing method implementations that PHPStan is complaining about
$methodFixes = [
    'app/Domain/Lending/Projections/Loan.php' => [
        'application' => <<<'METHOD'
    /**
     * Get the loan application.
     */
    public function application()
    {
        return $this->belongsTo(\App\Domain\Lending\Models\LoanApplication::class, 'application_id');
    }
METHOD,
        'borrower' => <<<'METHOD'
    /**
     * Get the borrower account.
     */
    public function borrower()
    {
        return $this->belongsTo(\App\Models\Account::class, 'borrower_account_id');
    }
METHOD
    ]
];

foreach ($methodFixes as $file => $methods) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $changed = false;
        
        foreach ($methods as $methodName => $methodCode) {
            if (!preg_match('/function\s+' . $methodName . '\s*\(/', $content)) {
                // Add method before last closing brace
                $lastBrace = strrpos($content, '}');
                $content = substr($content, 0, $lastBrace) . "\n\n" . $methodCode . "\n" . substr($content, $lastBrace);
                $changed = true;
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            $fixCount++;
            echo "Added methods to: $file\n";
        }
    }
}

// 4. Fix parameter type hints for methods expecting specific types
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix cases where auth()->user() is passed to methods expecting User type
    if (preg_match_all('/(\$\w+)->(\w+)\(auth\(\)->user\(\)/', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fullMatch = $match[0];
            $service = $match[1];
            $method = $match[2];
            
            // Wrap with user check
            $replacement = "auth()->check() ? {$service}->{$method}(auth()->user()) : null";
            $content = str_replace($fullMatch, $replacement, $content);
            $changed = true;
        }
    }
    
    // Fix relationship return type hints
    if (preg_match('/class\s+\w+\s+extends\s+Model/', $content)) {
        // Fix belongsTo relationships missing return type hints
        $content = preg_replace_callback(
            '/(public function \w+\(\))\s*\n\s*\{(\s*return \$this->belongsTo\([^)]+\);)/m',
            function($matches) {
                $declaration = $matches[1];
                $body = $matches[2];
                
                // Check if it already has PHPDoc
                if (!strpos($matches[0], '@return')) {
                    return "/**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n     */\n    " . $declaration . "\n    {" . $body;
                }
                return $matches[0];
            },
            $content
        );
        
        // Fix hasMany relationships
        $content = preg_replace_callback(
            '/(public function \w+\(\))\s*\n\s*\{(\s*return \$this->hasMany\([^)]+\);)/m',
            function($matches) {
                $declaration = $matches[1];
                $body = $matches[2];
                
                // Check if it already has PHPDoc
                if (!strpos($matches[0], '@return')) {
                    return "/**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\HasMany\n     */\n    " . $declaration . "\n    {" . $body;
                }
                return $matches[0];
            },
            $content
        );
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

// 5. Fix specific class not found issues
$classReplacements = [
    'App\Domain\Lending\Projections\Loan' => 'App\Domain\Lending\Models\Loan',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($classReplacements as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "\nTotal fixes applied: $fixCount\n";