<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath('bootstrap.php')
    ->notPath('bin/console')
    ->notPath('vendor/');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PHPCsFixer' => true,
        'strict_param' => true,
        'ordered_imports' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
