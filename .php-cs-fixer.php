<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/Classes')
    ->in(__DIR__ . '/Configuration')
    ->in(__DIR__ . '/Tests')
    ->name('*.php')
    // Generated Extbase classes (relative to Classes/)
    ->exclude('Domain/Model/Record')
    ->exclude('Domain/Repository/Record')
    // Generated datatype TCA (relative to Configuration/, not repo root)
    ->notPath('#^TCA/tx_tonictypes_domain_model_record_#');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    ])
    ->setFinder($finder);
