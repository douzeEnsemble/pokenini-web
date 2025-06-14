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
        ],
    ])
    ->setFinder($finder)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;