#!/usr/bin/env php
<?php

// Fix method return types and missing methods

// Fix ApiKey verify method
$apiKeyFile = 'app/Models/ApiKey.php';
if (file_exists($apiKeyFile)) {
    $content = file_get_contents($apiKeyFile);
    
    // Find the verify method and ensure it returns the ApiKey properly
    $content = preg_replace_callback(
        '/public static function verify\([^{]+\{([^}]+)\}/s',
        function($matches) {
            $methodBody = $matches[1];
            
            // Check if it already returns $apiKey
            if (!strpos($methodBody, 'return $apiKey;')) {
                // Find where it checks the hash and add return
                $methodBody = preg_replace(
                    '/(if\s*\(\$apiKey\s*&&\s*Hash::check[^}]+\})/s',
                    '$1' . "\n\n        return \$apiKey;",
                    $methodBody
                );
            }
            
            return 'public static function verify(string $plainKey): ?ApiKey {' . $methodBody . '}';
        },
        $content
    );
    
    file_put_contents($apiKeyFile, $content);
    echo "Fixed: $apiKeyFile\n";
}

// Fix missing logs() method on models
$modelFiles = array_merge(
    glob('app/Domain/*/Models/*.php'),
    glob('app/Models/*.php')
);

$fixedCount = 0;

foreach ($modelFiles as $file) {
    $content = file_get_contents($file);
    
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
            
            file_put_contents($file, $content);
            $fixedCount++;
            echo "Added logs() to: $file\n";
        }
    }
}

// Fix specific Loan model methods
$loanFile = 'app/Domain/Lending/Models/Loan.php';
if (file_exists($loanFile)) {
    $content = file_get_contents($loanFile);
    $changed = false;
    
    // Add missing relationships if they don't exist
    if (!preg_match('/function\s+application\s*\(/', $content)) {
        $method = <<<'METHOD'

    /**
     * Get the loan application.
     */
    public function application()
    {
        return $this->belongsTo(LoanApplication::class, 'application_id');
    }
METHOD;
        
        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace) . $method . "\n" . substr($content, $lastBrace);
        $changed = true;
    }
    
    if (!preg_match('/function\s+borrower\s*\(/', $content)) {
        $method = <<<'METHOD'

    /**
     * Get the borrower.
     */
    public function borrower()
    {
        return $this->belongsTo(Account::class, 'borrower_account_id');
    }
METHOD;
        
        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace) . $method . "\n" . substr($content, $lastBrace);
        $changed = true;
    }
    
    // Ensure proper imports
    if (strpos($content, 'LoanApplication') !== false && strpos($content, 'use App\Domain\Lending\Models\LoanApplication;') === false) {
        $content = preg_replace(
            '/(namespace\s+[^;]+;)/',
            "$1\n\nuse App\Domain\Lending\Models\LoanApplication;",
            $content
        );
        $changed = true;
    }
    
    if (strpos($content, 'Account::class') !== false && strpos($content, 'use App\Models\Account;') === false) {
        $content = preg_replace(
            '/(namespace\s+[^;]+;)/',
            "$1\n\nuse App\Models\Account;",
            $content
        );
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($loanFile, $content);
        $fixedCount++;
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
        $changed = false;
        
        // Check if asset() method needs fixing
        if (preg_match('/public function asset\(\)/', $content)) {
            // Ensure it has proper return type annotation
            if (!preg_match('/@return.*BelongsTo.*Asset.*\$this/', $content)) {
                // Add PHPDoc before the method
                $content = preg_replace(
                    '/(public function asset\(\))/',
                    "/**\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo<Asset, \$this>\n     */\n    $1",
                    $content
                );
                $changed = true;
            }
        }
        
        // Add Asset import if needed
        if (strpos($content, 'Asset::class') !== false && strpos($content, 'use App\Domain\Asset\Models\Asset;') === false) {
            $content = preg_replace(
                '/(namespace\s+[^;]+;)/',
                "$1\n\nuse App\Domain\Asset\Models\Asset;",
                $content
            );
            $changed = true;
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            $fixedCount++;
            echo "Fixed asset() in: $file\n";
        }
    }
}

echo "\nTotal fixed: $fixedCount files\n";