<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,

        // Array notation
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // Basic
        'braces_position' => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_blank_line_at_eof' => true,

        // Casts
        'cast_spaces' => ['space' => 'none'],
        'lowercase_cast' => true,
        'short_scalar_cast' => true,

        // Classes
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'no_blank_lines_after_class_opening' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],

        // Comments
        'comment_to_phpdoc' => true,
        'no_empty_comment' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],

        // Control structures
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,

        // Functions
        'lambda_not_used_import' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_spaces_after_function_name' => true,
        'return_type_declaration' => ['space_before' => 'none'],

        // Imports
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        // Language constructs
        'declare_strict_types' => true,
        'explicit_string_variable' => true,

        // Operators
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'concat_space' => ['spacing' => 'one'],
        'increment_style' => ['style' => 'post'],
        'not_operator_with_successor_space' => true,
        'object_operator_without_whitespace' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'unary_operator_spaces' => true,

        // PHPDoc
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,

        // Strings
        'single_quote' => true,
        'string_implicit_backslashes' => true,

        // Whitespace
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
                'use_trait',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
