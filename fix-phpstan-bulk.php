#!/usr/bin/env php
<?php

// Bulk fix script for common PHPStan issues

$fixedCount = 0;

// 1. Fix all firstOrFail() and first() type annotations
$files = glob('app/Domain/*/Activities/*.php');
$files = array_merge($files, glob('app/Domain/*/Services/*.php'));
$files = array_merge($files, glob('app/Http/Controllers/*.php'));

foreach ($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Add type annotations for firstOrFail() and first()
    $patterns = [
        // firstOrFail with assignment
        '/(\$\w+)\s*=\s*(\w+)::where\([^)]+\)->firstOrFail\(\);/' => function($matches) use (&$content, &$changed) {
            $var = $matches[1];
            $model = $matches[2];
            $statement = $matches[0];
            
            // Check if annotation already exists
            $beforeStatement = substr($content, max(0, strpos($content, $statement) - 200), 200);
            if (strpos($beforeStatement, "/** @var") === false || strpos($beforeStatement, $var) === false) {
                // Add annotation
                $indent = '        '; // Default indent
                if (preg_match('/\n(\s*)' . preg_quote($statement, '/') . '/', $content, $indentMatch)) {
                    $indent = $indentMatch[1];
                }
                
                $annotation = $indent . "/** @var {$model} {$var} */\n";
                $content = str_replace($statement, $annotation . $statement, $content);
                $changed = true;
            }
        },
        
        // first with assignment
        '/(\$\w+)\s*=\s*(\w+)::where\([^)]+\)->first\(\);/' => function($matches) use (&$content, &$changed) {
            $var = $matches[1];
            $model = $matches[2];
            $statement = $matches[0];
            
            // Check if annotation already exists
            $beforeStatement = substr($content, max(0, strpos($content, $statement) - 200), 200);
            if (strpos($beforeStatement, "/** @var") === false || strpos($beforeStatement, $var) === false) {
                // Add annotation
                $indent = '        '; // Default indent
                if (preg_match('/\n(\s*)' . preg_quote($statement, '/') . '/', $content, $indentMatch)) {
                    $indent = $indentMatch[1];
                }
                
                $annotation = $indent . "/** @var {$model}|null {$var} */\n";
                $content = str_replace($statement, $annotation . $statement, $content);
                $changed = true;
            }
        },
        
        // Fix auth()->user() calls
        '/(\$\w+)\s*=\s*auth\(\)->user\(\);/' => function($matches) use (&$content, &$changed) {
            $var = $matches[1];
            $statement = $matches[0];
            
            // Check if annotation already exists
            $beforeStatement = substr($content, max(0, strpos($content, $statement) - 200), 200);
            if (strpos($beforeStatement, "/** @var") === false || strpos($beforeStatement, $var) === false) {
                // Add annotation
                $indent = '        '; // Default indent
                if (preg_match('/\n(\s*)' . preg_quote($statement, '/') . '/', $content, $indentMatch)) {
                    $indent = $indentMatch[1];
                }
                
                $annotation = $indent . "/** @var \\App\\Models\\User {$var} */\n";
                $content = str_replace($statement, $annotation . $statement, $content);
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
        $fixedCount++;
        echo "Added annotations to: $file\n";
    }
}

// 2. Fix missing PHPDoc on all Model classes
$modelFiles = glob('app/Domain/*/Models/*.php');
$modelFiles = array_merge($modelFiles, glob('app/Models/*.php'));

foreach ($modelFiles as $file) {
    $content = file_get_contents($file);
    
    // Check if it's a Model class without PHPDoc
    if (preg_match('/^((?:(?!\/\*\*).)*?)(class\s+\w+\s+extends\s+Model)/ms', $content, $matches)) {
        $beforeClass = $matches[1];
        $classDecl = $matches[2];
        
        // Check if there's no @method annotation
        if (strpos($beforeClass, '@method') === false) {
            // Add comprehensive PHPDoc
            $phpDoc = <<<'DOC'
/**
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder whereNull(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder whereNotNull(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder whereDate(string $column, mixed $operator, string|\DateTimeInterface|null $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder whereMonth(string $column, mixed $operator, string|\DateTimeInterface|null $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder whereYear(string $column, mixed $value)
 * @method static \Illuminate\Database\Eloquent\Builder orderBy(string $column, string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder latest(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder oldest(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder with(array|string $relations)
 * @method static \Illuminate\Database\Eloquent\Builder distinct(string $column = null)
 * @method static \Illuminate\Database\Eloquent\Builder groupBy(string ...$groups)
 * @method static \Illuminate\Database\Eloquent\Builder having(string $column, string $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder selectRaw(string $expression, array $bindings = [])
 * @method static \Illuminate\Database\Eloquent\Collection get(array $columns = ['*'])
 * @method static static|null find(mixed $id, array $columns = ['*'])
 * @method static static|null first(array $columns = ['*'])
 * @method static static firstOrFail(array $columns = ['*'])
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static static firstOrNew(array $attributes, array $values = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static static create(array $attributes = [])
 * @method static int count(string $columns = '*')
 * @method static mixed sum(string $column)
 * @method static mixed avg(string $column)
 * @method static mixed max(string $column)
 * @method static mixed min(string $column)
 * @method static bool exists()
 * @method static bool doesntExist()
 * @method static \Illuminate\Support\Collection pluck(string $column, string|null $key = null)
 * @method static bool delete()
 * @method static bool update(array $values)
 * @method static \Illuminate\Database\Eloquent\Builder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder query()
 */

DOC;
            $content = str_replace($classDecl, $phpDoc . $classDecl, $content);
            file_put_contents($file, $content);
            $fixedCount++;
            echo "Added PHPDoc to model: $file\n";
        }
    }
}

// 3. Fix common method calls
$allPhpFiles = glob('app/**/*.php', GLOB_BRACE);

foreach ($allPhpFiles as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // Fix DB::rollback to DB::rollBack
    if (strpos($content, 'DB::rollback') !== false) {
        $content = str_replace('DB::rollback', 'DB::rollBack', $content);
        $changed = true;
    }
    
    // Fix common import issues
    $imports = [
        'Transaction' => 'App\Domain\Transaction\Models\Transaction',
        'Order' => 'App\Domain\Exchange\Projections\Order',
        'BasketAsset' => 'App\Domain\Basket\Models\BasketAsset',
        'Account' => 'App\Models\Account',
        'User' => 'App\Models\User',
    ];
    
    foreach ($imports as $class => $namespace) {
        // Check if class is used but not imported
        if (preg_match('/\b' . $class . '::|new\s+' . $class . '\b|\(' . $class . '\s+\$/', $content) &&
            strpos($content, "use $namespace;") === false &&
            strpos($content, "namespace $namespace") === false) {
            
            // Add import after namespace
            if (preg_match('/namespace\s+[^;]+;/', $content, $nsMatch)) {
                $afterNamespace = substr($content, strpos($content, $nsMatch[0]) + strlen($nsMatch[0]));
                if (preg_match('/\n(use\s+[^;]+;)*/', $afterNamespace, $useMatch)) {
                    $lastUse = $nsMatch[0] . $useMatch[0];
                    $content = str_replace($lastUse, $lastUse . "\nuse $namespace;", $content);
                    $changed = true;
                }
            }
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        $fixedCount++;
        echo "Fixed imports/methods in: $file\n";
    }
}

echo "\nBulk fixes completed. Fixed $fixedCount files.\n";