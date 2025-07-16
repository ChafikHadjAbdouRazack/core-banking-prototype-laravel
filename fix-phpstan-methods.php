#!/usr/bin/env php
<?php

// Fix method return types and missing methods

$fixes = [
    // Fix ApiKey verify method
    'app/Models/ApiKey.php' => function($content) {
        // Ensure verify method returns self
        $content = preg_replace(
            '/public static function verify\([^{]+\{[^}]+\}/s',
            function($matches) {
                $method = $matches[0];
                // Ensure it returns the found ApiKey
                if (!strpos($method, 'return $apiKey;')) {
                    $method = preg_replace('/return\s+null;/', "return null;\n        }\n\n        return \$apiKey;", $method);
                }
                return $method;
            },
            $content
        );
        return $content;
    },
    
    // Fix missing logs() method on various models
    'app/Domain/*/Models/*.php' => function($content, $file) {
        // Check if it's a Model class
        if (preg_match('/class\s+\w+\s+extends\s+Model/', $content)) {
            // Check if logs() method is missing
            if (!preg_match('/function\s+logs\s*\(/', $content)) {
                // Add logs() relationship method
                $logsMethod = <<<'METHOD'

    /**
     * Get the activity logs for this model.
     */
    public function logs()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
METHOD;
                
                // Add before last closing brace
                $lastBrace = strrpos($content, '}');
                $content = substr($content, 0, $lastBrace) . $logsMethod . "\n" . substr($content, $lastBrace);
            }
        }
        return $content;
    }
];

// Process files
$processedCount = 0;

foreach ($fixes as $pattern => $fixer) {
    $files = glob($pattern, GLOB_BRACE);
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $newContent = $fixer($content, $file);
            
            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
                $processedCount++;
                echo "Fixed: $file\n";
            }
        }
    }
}

// Fix specific Loan model methods
$loanFile = 'app/Domain/Lending/Models/Loan.php';
if (file_exists($loanFile)) {
    $content = file_get_contents($loanFile);
    $changed = false;
    
    // Add missing relationships if they don't exist
    $relationships = [
        'application' => <<<'METHOD'
    /**
     * Get the loan application.
     */
    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }
METHOD,
        'borrower' => <<<'METHOD'
    /**
     * Get the borrower.
     */
    public function borrower()
    {
        return $this->belongsTo(Account::class, 'borrower_account_id');
    }
METHOD
    ];
    
    foreach ($relationships as $name => $method) {
        if (!preg_match('/function\s+' . $name . '\s*\(/', $content)) {
            // Add method before last closing brace
            $lastBrace = strrpos($content, '}');
            $content = substr($content, 0, $lastBrace) . "\n" . $method . "\n" . substr($content, $lastBrace);
            $changed = true;
        }
    }
    
    // Ensure proper imports
    $imports = [
        'LoanApplication' => 'App\Domain\Lending\Models\LoanApplication',
        'Account' => 'App\Models\Account',
    ];
    
    foreach ($imports as $class => $namespace) {
        if (strpos($content, $class) !== false && strpos($content, "use $namespace;") === false) {
            // Add import after namespace
            $content = preg_replace(
                '/(namespace\s+[^;]+;)/',
                "$1\n\nuse $namespace;",
                $content
            );
            $changed = true;
        }
    }
    
    if ($changed) {
        file_put_contents($loanFile, $content);
        $processedCount++;
        echo "Fixed: $loanFile\n";
    }
}

// Fix AccountBalance and BasketComponent asset() methods
$assetRelationFiles = [
    'app/Domain/Account/Models/AccountBalance.php',
    'app/Domain/Basket/Models/BasketComponent.php',
];

foreach ($assetRelationFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Fix asset() method return type hint
        $content = preg_replace(
            '/public function asset\(\)[\s\n]*\{/',
            "public function asset()\n    {",
            $content
        );
        
        // Ensure it returns BelongsTo relationship
        $content = preg_replace(
            '/(public function asset\(\)[^{]*\{[^}]+return\s+\$this->belongsTo\()([^,)]+)/',
            '$1Asset::class',
            $content
        );
        
        // Add Asset import if needed
        if (strpos($content, 'Asset::class') !== false && strpos($content, 'use App\Domain\Asset\Models\Asset;') === false) {
            $content = preg_replace(
                '/(namespace\s+[^;]+;)/',
                "$1\n\nuse App\Domain\Asset\Models\Asset;",
                $content
            );
        }
        
        file_put_contents($file, $content);
        $processedCount++;
        echo "Fixed: $file\n";
    }
}

echo "\nFixed $processedCount files\n";