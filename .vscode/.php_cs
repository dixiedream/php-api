<?php

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
         'binary_operator_spaces' => [
            'align_double_arrow' => false,
            'align_equals' => false,
        ],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'concat_space' => ['spacing' => 'one'],
        'combine_consecutive_unsets' => true,
        'declare_equal_normalize' => true,
        'encoding' => true,
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'include' => true,
        'lowercase_cast' => true,
        'method_separation' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'single_quote' => true,
        'no_extra_consecutive_blank_lines' => [
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_spaces_around_offset' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'single_blank_line_before_namespace' => true,
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setUsingCache(true)
    ->setLineEnding("\n");
