#!/usr/bin/env php
<?php

// Targeted PHPStan error fixes based on actual error patterns

$fixCount = 0;

// 1. Fix PHPDoc @var tags that don't specify variable names
echo "Fixing PHPDoc @var tags without variable names...\n";
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Pattern 1: /** @var Type */ on one line, $variable = on next line
    $content = preg_replace(
        '/\/\*\*\s*@var\s+([^\s\*]+)\s*\*\/\s*\n(\s*)(\$\w+)\s*=/',
        "/** @var $1 $3 */\n$2$3 =",
        $content
    );
    
    // Pattern 2: /** @var Type */ with extra spaces/newlines before variable
    $content = preg_replace(
        '/\/\*\*\s*@var\s+([^\s\*]+)\s*\*\/\s*\n\s*\n(\s*)(\$\w+)\s*=/',
        "/** @var $1 $3 */\n$2$3 =",
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
        echo "Fixed PHPDoc in: $file\n";
    }
}

// 2. Fix undefined variable errors in specific files
$variableFixes = [
    'app/Domain/Account/Activities/CreateAccountActivity.php' => [
        'account' => 'Account',
    ],
    'app/Domain/Account/Activities/DebitAccountActivity.php' => [
        'account' => 'Account',
    ],
    'app/Domain/Account/Activities/CreditAccountActivity.php' => [
        'account' => 'Account',
    ],
    'app/Domain/Account/Activities/TransferFundsActivity.php' => [
        'fromAccount' => 'Account',
        'toAccount' => 'Account',
    ],
    'app/Domain/Basket/Activities/CalculateBasketValueActivity.php' => [
        'basket' => 'BasketAsset',
    ],
];

foreach ($variableFixes as $file => $variables) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($variables as $var => $type) {
            // Find where variable is first used
            if (preg_match('/\$' . $var . '\s*=\s*([^;]+);/', $content, $matches)) {
                $assignment = $matches[0];
                $fullType = match($type) {
                    'Account' => '\\App\\Models\\Account',
                    'BasketAsset' => '\\App\\Domain\\Basket\\Models\\BasketAsset',
                    default => $type
                };
                
                // Add @var annotation if not present
                if (!preg_match('/@var[^\\n]*\\$' . $var . '/', $content)) {
                    $content = preg_replace(
                        '/(' . preg_quote($assignment, '/') . ')/',
                        "/** @var {$fullType}|null \${$var} */\n        $1",
                        $content,
                        1
                    );
                }
            }
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $fixCount++;
            echo "Fixed variables in: $file\n";
        }
    }
}

// 3. Fix missing imports
$importFixes = [
    'App\\Domain\\Asset\\Models\\AccountBalance' => 'app/Domain/Asset/Models/AccountBalance.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($importFixes as $wrongImport => $correctFile) {
        if (strpos($content, $wrongImport) !== false) {
            // Replace with correct namespace
            $content = str_replace($wrongImport, 'App\\Domain\\Account\\Models\\AccountBalance', $content);
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
    }
}

// 4. Add missing methods to models
$methodsToAdd = [
    'app/Domain/Banking/Models/BankConnection.php' => [
        'isActive' => <<<'METHOD'
    /**
     * Check if the bank connection is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }
METHOD
    ],
];

foreach ($methodsToAdd as $file => $methods) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($methods as $methodName => $methodCode) {
            if (!preg_match('/function\s+' . $methodName . '\s*\(/', $content)) {
                // Add method before last closing brace
                $lastBrace = strrpos($content, '}');
                $content = substr($content, 0, $lastBrace) . "\n" . $methodCode . "\n" . substr($content, $lastBrace);
            }
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $fixCount++;
            echo "Added methods to: $file\n";
        }
    }
}

// 5. Fix increment/decrement method calls
$files = glob('app/Domain/Account/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace ->increment() with ->update() for protected methods
    $content = preg_replace(
        '/\$(\w+)->increment\(([\'"]\w+[\'"])\s*,\s*(\$?\w+)\)/',
        '$$1->update([$$2 => $$1->$$2 + $3])',
        $content
    );
    
    // Replace ->decrement() with ->update() for protected methods
    $content = preg_replace(
        '/\$(\w+)->decrement\(([\'"]\w+[\'"])\s*,\s*(\$?\w+)\)/',
        '$$1->update([$$2 => $$1->$$2 - $3])',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixCount++;
        echo "Fixed increment/decrement in: $file\n";
    }
}

echo "\nTotal fixes applied: $fixCount\n";