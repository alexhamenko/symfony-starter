<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
        'importmap.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP8x5Migration' => true,
        '@PHP8x5Migration:risky' => true,
        'declare_strict_types' => true,
        // Every concrete class is final, except Doctrine-mapped ones (proxies extend them)
        'final_internal_class' => [
            'include' => [],
            'consider_absent_docblock_as_internal_class' => true,
            'exclude' => ['final', 'Entity', 'ORM\Entity', 'ORM\Embeddable', 'ORM\MappedSuperclass'],
        ],
    ])
    ->setFinder($finder)
;
