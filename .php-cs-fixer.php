<?php

$fileHeaderComment = <<<COMMENT
This file is part of Bilemo

(c)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

$finder = (new PhpCsFixer\Finder())
->in(__DIR__)
->exclude('config')
->exclude('var')
->exclude('public/bundles')
->exclude('public/build')
// exclude files generated by Symfony Flex recipes
->notPath('bin/console')
->notPath('public/index.php')
->exclude(['tests', 'vendor', 'node_modules']) // Codeception & Symfony coding standards are different
;

$config = new PhpCsFixer\Config();

return $config
->setRiskyAllowed(true)
->setRules([
'@Symfony' => true,
'@Symfony:risky' => true,
'array_syntax' => ['syntax' => 'short'],
'header_comment' => ['header' => $fileHeaderComment, 'separate' => 'both'],
'linebreak_after_opening_tag' => true,
'mb_str_functions' => true,
'no_php4_constructor' => true,
'no_unreachable_default_argument_value' => true,
'no_useless_else' => true,
'no_useless_return' => true,
'ordered_imports' => true,
'php_unit_strict' => true,
'phpdoc_order' => true,
'semicolon_after_instruction' => true,
'strict_comparison' => true,
'strict_param' => true,
    'declare_strict_types' => true,
])
->setFinder($finder)
->setCacheFile(__DIR__.'/var/.php_cs.cache')
;