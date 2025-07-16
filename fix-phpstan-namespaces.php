#!/usr/bin/env php
<?php

// Fix namespace issues in PHPStan

$replacements = [
    // Wrong namespaces to correct ones
    'App\Domain\Asset\Models\AccountBalance' => 'App\Domain\Account\Models\AccountBalance',
    'App\Domain\Stablecoin\Models\Account' => 'App\Models\Account',
    'App\Domain\Stablecoin\Models\User' => 'App\Models\User',
    'App\Domain\Account\Models\ApiKey' => 'App\Models\ApiKey',
    'App\Domain\Account\Models\AuditLog' => 'App\Models\AuditLog',
    'App\Domain\Account\Models\Transaction' => 'App\Models\Transaction',
    'App\Domain\Lending\Models\User' => 'App\Models\User',
    'App\Models\UserBankPreference' => 'App\Domain\Banking\Models\UserBankPreference',
    
    // Add use statements if missing
    'use App\Domain\Governance\Models\PollType;' => "use App\Domain\Governance\Enums\PollType;\nuse App\Domain\Governance\Enums\PollStatus;",
    'use App\Domain\Governance\Models\PollStatus;' => '',
];

$files = glob('app/**/*.php', GLOB_BRACE);
$fixedCount = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Apply replacements
    foreach ($replacements as $search => $replace) {
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
        }
    }
    
    // Fix specific patterns
    // Fix User::role() to User::hasRole()
    $content = preg_replace('/User::role\(\)/', 'User::hasRole', $content);
    
    // Fix missing imports for commonly used classes
    $classesNeedingImport = [
        'AccountBalance' => 'App\Domain\Account\Models\AccountBalance',
        'TransactionProjection' => 'App\Domain\Account\Models\TransactionProjection',
        'Account' => 'App\Models\Account',
        'User' => 'App\Models\User',
        'Transaction' => 'App\Models\Transaction',
        'ApiKey' => 'App\Models\ApiKey',
        'AuditLog' => 'App\Models\AuditLog',
    ];
    
    foreach ($classesNeedingImport as $class => $namespace) {
        // Check if class is used but not imported
        if (preg_match('/\b' . $class . '::|new\s+' . $class . '\b|\(' . $class . '\s+\$/', $content) &&
            strpos($content, "use $namespace;") === false &&
            strpos($content, "namespace $namespace") === false) {
            
            // Add import after namespace
            if (preg_match('/namespace\s+[^;]+;/', $content, $nsMatch)) {
                $afterNamespace = substr($content, strpos($content, $nsMatch[0]) + strlen($nsMatch[0]));
                
                // Find existing use statements
                if (preg_match_all('/\nuse\s+[^;]+;/', $afterNamespace, $useMatches, PREG_OFFSET_CAPTURE)) {
                    // Add after last use statement
                    $lastUse = end($useMatches[0]);
                    $insertPos = strpos($content, $nsMatch[0]) + strlen($nsMatch[0]) + $lastUse[1] + strlen($lastUse[0]);
                    $content = substr($content, 0, $insertPos) . "\nuse $namespace;" . substr($content, $insertPos);
                } else {
                    // Add after namespace
                    $content = str_replace($nsMatch[0], $nsMatch[0] . "\n\nuse $namespace;", $content);
                }
            }
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedCount++;
        echo "Fixed: $file\n";
    }
}

// Fix enum files
$enumFiles = [
    'app/Domain/Governance/Enums/PollType.php',
    'app/Domain/Governance/Enums/PollStatus.php',
];

foreach ($enumFiles as $enumFile) {
    if (!file_exists($enumFile)) {
        $enumName = basename($enumFile, '.php');
        $namespace = str_replace(['app/', '/'], ['App\\', '\\'], dirname($enumFile));
        
        $enumContent = "<?php\n\nnamespace $namespace;\n\nenum $enumName: string\n{\n";
        
        if ($enumName === 'PollType') {
            $enumContent .= "    case GOVERNANCE = 'governance';\n";
            $enumContent .= "    case REFERENDUM = 'referendum';\n";
            $enumContent .= "    case PROPOSAL = 'proposal';\n";
        } elseif ($enumName === 'PollStatus') {
            $enumContent .= "    case DRAFT = 'draft';\n";
            $enumContent .= "    case ACTIVE = 'active';\n";
            $enumContent .= "    case CLOSED = 'closed';\n";
            $enumContent .= "    case CANCELLED = 'cancelled';\n";
        }
        
        $enumContent .= "}\n";
        
        // Create directory if needed
        $dir = dirname($enumFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($enumFile, $enumContent);
        echo "Created enum: $enumFile\n";
        $fixedCount++;
    }
}

echo "\nFixed $fixedCount files\n";