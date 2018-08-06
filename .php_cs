<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('cache')
    ->exclude('log')
    ->exclude('var')
    ->exclude('vendor')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2'                                 => true,
        'array_syntax'                          => ['syntax' => 'short'],
        'blank_line_after_opening_tag'          => true,
        'combine_consecutive_issets'            => true,
        'combine_consecutive_unsets'            => true,
        'concat_space'                          => ['spacing' => 'one'],
        'native_function_casing'                => true,
        'no_blank_lines_after_class_opening'    => true,
        'no_blank_lines_after_phpdoc'           => true,
        'no_empty_comment'                      => true,
        'no_empty_phpdoc'                       => true,
        'no_empty_statement'                    => true,
        'no_leading_import_slash'               => true,
        'no_leading_namespace_whitespace'       => true,
        'no_mixed_echo_print'                   => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports'                     => true,
        'phpdoc_scalar'                         => true,
        'phpdoc_single_line_var_spacing'        => true,
        'short_scalar_cast'                     => true,
        'single_quote'                          => true,
        'standardize_not_equals'                => true,
        'trailing_comma_in_multiline_array'     => true,
    ])
    ->setFinder($finder);
