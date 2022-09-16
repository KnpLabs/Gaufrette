<?php

return (new PhpCsFixer\Config())
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude('vendor')
        ->in(__DIR__)
    )
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        'array_syntax' => [ 'syntax' => 'short' ],
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => true,
        'cast_spaces' => true,
        'combine_consecutive_unsets' => true,
        'concat_space' => [ 'spacing' => 'one' ],
        'linebreak_after_opening_tag' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_break_comment' => false,
        'no_extra_blank_lines' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'PedroTroller/phpspec' => true,
        'phpdoc_indent' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'single_quote' => true,
        'ternary_to_null_coalescing' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'trim_array_spaces' => true,
    ])
    ->registerCustomFixers(new PedroTroller\CS\Fixer\Fixers())
;
