#!/usr/bin/env php
<?php

// Fix missing scopes and methods on models

$fixes = [
    // Fix FraudCase model
    'app/Domain/Fraud/Models/FraudCase.php' => function($content) {
        $additions = [];
        
        // Add missing @method annotations
        if (strpos($content, '@method static') === false) {
            $additions[] = <<<'DOC'
/**
 * @method static \Illuminate\Database\Eloquent\Builder whereHas(string $relation, \Closure $callback = null, string $operator = '>=', int $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Collection get(array $columns = ['*'])
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static|null first(array $columns = ['*'])
 * @method static static create(array $attributes = [])
 */
DOC;
        }
        
        // Add allTeams scope
        if (strpos($content, 'scopeAllTeams') === false) {
            $additions[] = <<<'METHOD'
    /**
     * Scope to get cases for all teams.
     */
    public function scopeAllTeams($query)
    {
        return $query;
    }
METHOD;
        }
        
        return addToClass($content, $additions);
    },
    
    // Fix GcuVotingProposal model
    'app/Domain/Governance/Models/GcuVotingProposal.php' => function($content) {
        $additions = [];
        
        // Add missing methods
        if (strpos($content, 'scopeUpcoming') === false) {
            $additions[] = <<<'METHOD'
    /**
     * Scope for upcoming proposals.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('voting_starts_at', '>', now())
            ->orderBy('voting_starts_at', 'asc');
    }
METHOD;
        }
        
        if (strpos($content, 'scopePast') === false) {
            $additions[] = <<<'METHOD'
    /**
     * Scope for past proposals.
     */
    public function scopePast($query)
    {
        return $query->where('voting_ends_at', '<', now())
            ->orderBy('voting_ends_at', 'desc');
    }
METHOD;
        }
        
        return addToClass($content, $additions);
    },
    
    // Fix GcuVote model
    'app/Domain/Governance/Models/GcuVote.php' => function($content) {
        $additions = [];
        
        // Add missing @method annotations
        if (strpos($content, '@method static updateOrCreate') === false) {
            $additions[] = <<<'DOC'
/**
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null first(array $columns = ['*'])
 * @method static static create(array $attributes = [])
 */
DOC;
        }
        
        return addToClass($content, $additions);
    },
    
    // Fix Account namespace in GcuVotingController
    'app/Http/Controllers/GcuVotingController.php' => function($content) {
        // Fix namespace
        if (strpos($content, 'use App\Domain\AccountBalance\Models\AccountBalance;') !== false) {
            $content = str_replace(
                'use App\Domain\AccountBalance\Models\AccountBalance;',
                'use App\Domain\Account\Models\AccountBalance;',
                $content
            );
        }
        return $content;
    },
    
    // Fix LendingController
    'app/Http/Controllers/LendingController.php' => function($content) {
        // The submitApplication method should already be fixed to use ->toArray()
        if (strpos($content, '->submitApplication($applicationData)') !== false && 
            strpos($content, '->submitApplication($applicationData->toArray())') === false) {
            $content = str_replace(
                '->submitApplication($applicationData)',
                '->submitApplication($applicationData->toArray())',
                $content
            );
        }
        return $content;
    },
    
    // Fix ApiKey model
    'app/Models/ApiKey.php' => function($content) {
        // Fix verify method return type
        if (preg_match('/public function verify\([^)]*\)/', $content)) {
            $content = preg_replace(
                '/public function verify\(([^)]*)\)/',
                'public function verify($1): ?ApiKey',
                $content
            );
            
            // Fix the return statement
            $content = preg_replace(
                '/return\s+\$this->update\(/',
                'return $this->update(',
                $content
            );
            
            // Ensure method returns $this
            $content = preg_replace(
                '/(public function verify[^{]+\{[^}]+\})/s',
                function($matches) {
                    $method = $matches[1];
                    if (strpos($method, 'return $this;') === false) {
                        $method = preg_replace('/\}\s*$/', "\n        return \$this;\n    }", $method);
                    }
                    return $method;
                },
                $content
            );
        }
        return $content;
    }
];

function addToClass($content, $additions) {
    if (empty($additions)) {
        return $content;
    }
    
    // Find class declaration
    if (preg_match('/class\s+\w+[^{]*\{/', $content, $matches)) {
        $classStart = strpos($content, $matches[0]);
        
        // Add PHPDoc before class if needed
        foreach ($additions as $i => $addition) {
            if (strpos($addition, '/**') === 0) {
                // This is PHPDoc, add before class
                $beforeClass = substr($content, 0, $classStart);
                if (strpos($beforeClass, '@method') === false) {
                    $content = substr($content, 0, $classStart) . $addition . "\n" . substr($content, $classStart);
                }
                unset($additions[$i]);
            }
        }
        
        // Add methods to class
        if (!empty($additions)) {
            $lastBrace = strrpos($content, '}');
            $methodsToAdd = "\n" . implode("\n\n", $additions) . "\n";
            $content = substr($content, 0, $lastBrace) . $methodsToAdd . substr($content, $lastBrace);
        }
    }
    
    return $content;
}

$fixedCount = 0;

foreach ($fixes as $file => $fixer) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $newContent = $fixer($content);
        
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            $fixedCount++;
            echo "Fixed: $file\n";
        }
    }
}

echo "\nFixed $fixedCount files\n";