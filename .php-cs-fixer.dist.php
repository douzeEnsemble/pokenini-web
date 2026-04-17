<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->notPath([
        'bootstrap.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@Symfony' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PHP83Migration' => true,
        'phpdoc_to_comment' => [
            'allow_before_return_statement' => true,
            'ignored_tags' => [
                'phpstan-ignore',
                'psalm-suppress',
            ],
        ],
        'declare_strict_types' => true,
        'psr_autoloading' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__.'/var/cache/phpcsfixer/.php-cs-fixer.cache')
;
