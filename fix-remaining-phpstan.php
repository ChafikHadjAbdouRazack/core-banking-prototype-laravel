#!/usr/bin/env php
<?php

$fixCount = 0;

// 1. Fix Asset model - ensure it extends Model
$file = 'app/Domain/Asset/Models/Asset.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Ensure it extends Model
    if (!preg_match('/extends\s+Model/', $content)) {
        $content = preg_replace(
            '/class\s+Asset\s*\{/',
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
    }
    
    file_put_contents($file, $content);
    $fixCount++;
}

// 2. Fix BasketValue model
$file = 'app/Domain/Basket/Models/BasketValue.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Ensure it extends Model
    if (!preg_match('/extends\s+Model/', $content)) {
        $content = preg_replace(
            '/class\s+BasketValue\s*\{/',
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
    }
    
    file_put_contents($file, $content);
    $fixCount++;
}

// 3. Add missing scopes to models
$scopesToAdd = [
    'active' => <<<'SCOPE'
    /**
     * Scope a query to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->orWhere('is_active', true)
            ->orWhereNull('deleted_at');
    }
SCOPE,
    'valid' => <<<'SCOPE'
    /**
     * Scope a query to only include valid records.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true)
            ->orWhere('status', '!=', 'invalid')
            ->orWhereNotNull('validated_at');
    }
SCOPE,
];

// Models that need active scope
$modelsNeedingActiveScope = [
    'app/Domain/Asset/Models/ExchangeRate.php',
    'app/Domain/Banking/Models/BankConnection.php',
    'app/Domain/Cgo/Models/CgoInvestment.php',
    'app/Domain/Governance/Models/Poll.php',
    'app/Domain/Lending/Models/LoanApplication.php',
];

foreach ($modelsNeedingActiveScope as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if active scope already exists
        if (!preg_match('/scopeActive/', $content)) {
            // Add scope before last closing brace
            $lastBrace = strrpos($content, '}');
            $content = substr($content, 0, $lastBrace) . "\n" . $scopesToAdd['active'] . "\n" . substr($content, $lastBrace);
            
            file_put_contents($file, $content);
            $fixCount++;
            echo "Added active scope to: $file\n";
        }
    }
}

// 4. Fix Query Builder vs Eloquent Builder issues
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix DB::table()->with() calls (Query Builder doesn't have with())
    $content = preg_replace_callback(
        '/DB::table\([^)]+\)([^;]+)->with\([^)]+\)/',
        function($matches) {
            // Replace with model query
            if (preg_match('/table\([\'"](\w+)[\'"]\)/', $matches[0], $tableMatch)) {
                $table = $tableMatch[1];
                $modelName = ucfirst(Str::singular($table));
                return $modelName . '::query()' . $matches[1] . '->with()';
            }
            return $matches[0];
        },
        $content
    );
    
    // Fix User::role() to User::hasRole()
    $content = str_replace('User::role()', 'User::hasRole', $content);
    
    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        $fixCount++;
        $changed = true;
    }
}

// 5. Fix namespace issues
$namespaceReplacements = [
    'App\Domain\Stablecoin\Models\Account' => 'App\Models\Account',
    'App\Domain\Stablecoin\Models\User' => 'App\Models\User',
    'App\Domain\Lending\Models\User' => 'App\Models\User',
    'App\Domain\Asset\Models\AccountBalance' => 'App\Domain\Account\Models\AccountBalance',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    foreach ($namespaceReplacements as $old => $new) {
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

// 6. Fix Loan model relationships
$file = 'app/Domain/Lending/Models/Loan.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix application() method
    if (preg_match('/public function application\(\)/', $content)) {
        // Add proper return type hint
        $content = preg_replace(
            '/(\/\*\*\s*\*[^*]*\*\/\s*public function application\(\))/',
            "/**\n     * Get the loan application.\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n     */\n    public function application()",
            $content
        );
        $changed = true;
    }
    
    // Fix borrower() method
    if (preg_match('/public function borrower\(\)/', $content)) {
        // Add proper return type hint
        $content = preg_replace(
            '/(\/\*\*\s*\*[^*]*\*\/\s*public function borrower\(\))/',
            "/**\n     * Get the borrower.\n     * @return \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo\n     */\n    public function borrower()",
            $content
        );
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

// 7. Fix parameter type issues for basket methods
$basketFiles = glob('app/Domain/Basket/**/*.php', GLOB_BRACE);

foreach ($basketFiles as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Add type hints for basket parameters
    $content = preg_replace_callback(
        '/function\s+(\w+)\s*\(\s*\$basket\s*[,)]/',
        function($matches) {
            return 'function ' . $matches[1] . '(BasketAsset $basket' . substr($matches[0], -1);
        },
        $content
    );
    
    // Ensure BasketAsset is imported
    if (strpos($content, 'BasketAsset $basket') !== false && strpos($content, 'use App\Domain\Basket\Models\BasketAsset;') === false) {
        $content = preg_replace(
            '/(namespace[^;]+;)/',
            "$1\n\nuse App\\Domain\\Basket\\Models\\BasketAsset;",
            $content
        );
        $changed = true;
    }
    
    if ($changed || $content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

echo "\nTotal fixes applied: $fixCount\n";