#!/usr/bin/env php
<?php

// Aggressive PHPStan fix script

$totalFixed = 0;

// 1. Fix all auth()->user() null checks
$files = glob('app/**/*.php', GLOB_BRACE);

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Pattern to find service method calls with auth()->user()
    $patterns = [
        // Service method calls
        '/(\$\w+)->(\w+)\(auth\(\)->user\(\)([^)]*)\)/' => function($matches) use (&$content, &$changed) {
            $service = $matches[1];
            $method = $matches[2];
            $extraParams = $matches[3];
            
            // Wrap with null check
            $replacement = "auth()->check() ? {$service}->{$method}(auth()->user(){$extraParams}) : throw new \\Exception('User not authenticated')";
            $content = str_replace($matches[0], $replacement, $content);
            $changed = true;
        },
        
        // Direct auth()->user() assignments without annotation
        '/^(\s*)(\$\w+)\s*=\s*auth\(\)->user\(\);/m' => function($matches) use (&$content, &$changed) {
            $indent = $matches[1];
            $var = $matches[2];
            $statement = $matches[0];
            
            // Check if annotation already exists
            $lines = explode("\n", $content);
            $lineNum = 0;
            foreach ($lines as $i => $line) {
                if (trim($line) === trim($statement)) {
                    $lineNum = $i;
                    break;
                }
            }
            
            if ($lineNum > 0 && !str_contains($lines[$lineNum - 1] ?? '', '@var')) {
                // Add annotation and null check
                $replacement = "{$indent}/** @var \\App\\Models\\User {$var} */\n{$statement}\n{$indent}if (!{$var}) {\n{$indent}    throw new \\Exception('User not authenticated');\n{$indent}}";
                $content = str_replace($statement, $replacement, $content);
                $changed = true;
            }
        }
    ];
    
    foreach ($patterns as $pattern => $fixer) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fixer($match);
            }
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $totalFixed++;
        echo "Fixed auth issues in: $file\n";
    }
}

// 2. Add missing method stubs to models that need them
$modelAdditions = [
    'app/Domain/Fraud/Models/FraudCase.php' => [
        'load' => 'public function load($relations) { return $this->loadMissing($relations); }'
    ],
    'app/Domain/Governance/Models/GcuVotingProposal.php' => [
        'load' => 'public function load($relations) { return $this->loadMissing($relations); }'
    ]
];

foreach ($modelAdditions as $file => $methods) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $changed = false;
        
        foreach ($methods as $methodName => $methodCode) {
            if (!preg_match('/function\s+' . preg_quote($methodName) . '\s*\(/', $content)) {
                // Add method before last closing brace
                $lastBrace = strrpos($content, '}');
                $content = substr($content, 0, $lastBrace) . "\n\n    " . $methodCode . "\n" . substr($content, $lastBrace);
                $changed = true;
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            $totalFixed++;
            echo "Added methods to: $file\n";
        }
    }
}

// 3. Fix all nullable type hints on old PHP 7 style
foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix function parameters with $param = null but no ? prefix
    $content = preg_replace_callback(
        '/function\s+\w+\(([^)]+)\)/',
        function($matches) use (&$changed) {
            $params = $matches[1];
            
            // Process each parameter
            $newParams = preg_replace_callback(
                '/(\w+)\s+(\$\w+)\s*=\s*null/',
                function($paramMatch) use (&$changed) {
                    $type = $paramMatch[1];
                    $param = $paramMatch[2];
                    
                    // Don't add ? if already there or if type is mixed
                    if ($type === 'mixed' || $type[0] === '?') {
                        return $paramMatch[0];
                    }
                    
                    $changed = true;
                    return "?{$type} {$param} = null";
                },
                $params
            );
            
            return "function " . explode('(', $matches[0])[0] . "({$newParams})";
        },
        $content
    );
    
    if ($changed) {
        file_put_contents($file, $content);
        $totalFixed++;
        echo "Fixed nullable types in: $file\n";
    }
}

// 4. Fix specific controller issues
$controllerFixes = [
    'app/Http/Controllers/LendingController.php' => function($content) {
        // Ensure LoanApplication is converted to array
        if (!str_contains($content, '->toArray()')) {
            $content = str_replace(
                '->submitApplication($applicationData)',
                '->submitApplication($applicationData->toArray())',
                $content
            );
        }
        return $content;
    },
    
    'app/Http/Controllers/OpenBankingWithdrawalController.php' => function($content) {
        // Add account type check
        $pattern = '/(\$account\s*=\s*\$withdrawal->account;)/';
        if (preg_match($pattern, $content) && !str_contains($content, 'instanceof \App\Models\Account')) {
            $content = preg_replace(
                $pattern,
                "$1\n        if (!\$account instanceof \\App\\Models\\Account) {\n            return redirect()->route('dashboard')->with('error', 'Invalid account');\n        }",
                $content
            );
        }
        return $content;
    }
];

foreach ($controllerFixes as $file => $fixer) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = $fixer($content);
        
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            $totalFixed++;
            echo "Fixed controller: $file\n";
        }
    }
}

echo "\nAggressive fixes completed. Fixed $totalFixed files.\n";