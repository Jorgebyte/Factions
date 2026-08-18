<?php

$header = <<<EOF
This file is part of the Factions plugin for StreesCraft.

(c) 2026 Jorgebyte

Website:   https://jorgebyte.com
Community: https://discord.jorgebyte.com
Instagram: @jorgebyte_

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([

        '@PSR12' => true,

        // --- Strict ---
        'declare_strict_types' => true,

        // --- Header ---
        'header_comment' => [
            'header' => $header,
            'comment_type' => 'PHPDoc',
            'location' => 'after_declare_strict',
        ],

        // --- Imports ---
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_line_after_imports' => true,

        // --- Classes ---
        'ordered_class_elements' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],

        // --- Arrays ---
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => true,

        // --- Whitespace ---
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        'single_blank_line_at_eof' => true,

        // --- PHPDoc ---
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'no_superfluous_phpdoc_tags' => false,

        // --- Functions ---
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'return_type_declaration' => ['space_before' => 'none'],

        // --- Operators ---
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],

        // --- Misc ---
        'native_function_casing' => true,
        'modernize_types_casting' => true,
        'self_accessor' => true,

    ])
    ->setFinder($finder);