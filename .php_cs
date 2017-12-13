<?php
$finder = PhpCsFixer\Finder::create()
->exclude('vendor')
->notPath('_ide_helper.php')
->in(__DIR__);

return PhpCsFixer\Config::create()
	->setFinder($finder)
	->setIndent("\t")
    ->setLineEnding("\n")
	->setRules([
		'@PSR2' => true,
		'array_syntax' => ['syntax' => 'short'],
		'concat_space' => ['spacing' => 'one'],
		'indentation_type' => true,
		'binary_operator_spaces' => ['default' => 'single_space'],
		'combine_consecutive_issets' => true,

		'class_definition' => [
			'singleLine' => true,
			'singleItemSingleLine' => true,
			'multiLineExtendsEachSingleLine' => true
		],
		'braces' => [
			'allow_single_line_closure' => true,
			'position_after_anonymous_constructs' => 'same',
			'position_after_control_structures' => 'same',
			'position_after_functions_and_oop_constructs' => 'same'
		]
	]);

# vim: ft=php
