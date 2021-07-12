<?php
declare(strict_types=1);

$header = <<<EOF
GpsLab component.

@author    Peter Gribanov <info@peter-gribanov.ru>
@copyright Copyright (c) 2017, Peter Gribanov
@license   http://opensource.org/licenses/MIT
EOF;

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'header_comment' => [
            'commentType' => 'PHPDoc',
            'header' => $header,
        ],
        'class_definition' => [
            'multiLineExtendsEachSingleLine' => true,
        ],
        'no_superfluous_phpdoc_tags' => false,
        'blank_line_after_opening_tag' => false,
        'phpdoc_no_empty_return' => false,
        'yoda_style' => false,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->notPath('bootstrap.php')
    )
;
