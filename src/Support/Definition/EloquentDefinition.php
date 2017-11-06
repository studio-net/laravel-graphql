<?php
namespace StudioNet\GraphQL\Support\Definition;

use StudioNet\GraphQL\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Define some useful methods to perform a Type creation without creating many
 * and many classes
 *
 * @see DefinitionInterface
 * @abstract
 */
abstract class EloquentDefinition extends Definition {
	/** @var List of transformers to apply when needed $transformers */
	public $transformers = [
		'list'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\ListTransformer::class,
		'view'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\ViewTransformer::class,
		'drop'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\DropTransformer::class,
		'store' => \StudioNet\GraphQL\Support\Transformer\Eloquent\StoreTransformer::class,
		'batch' => \StudioNet\GraphQL\Support\Transformer\Eloquent\BatchTransformer::class
	];

	/**
	 * Return wanted transformers
	 *
	 * @return array
	 */
	public function getTransformers() {
		return [
			'list'  => true,
			'view'  => true,
			'drop'  => true,
			'store' => true,
			'batch' => true
		];
	}

	/**
	 * @override
	 *
	 * @return array
	 */
	protected function resolveFields() {
		$fields = parent::resolveFields();
		$traits = class_uses($this->getSource());

		if (in_array(SoftDeletes::class, $traits)) {
			$fields['trashed'] = [
				'type'        => Type::bool(),
				'description' => 'Is model deleted ?'
			];
		}

		return $fields;
	}
}
