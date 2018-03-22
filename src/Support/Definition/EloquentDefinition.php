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
	/** @var array $transformers List of transformers to apply when needed */
	public $transformers = [
		'list' => \StudioNet\GraphQL\Support\Transformer\Eloquent\ListTransformer::class,
		'view' => \StudioNet\GraphQL\Support\Transformer\Eloquent\ViewTransformer::class,
		'drop' => \StudioNet\GraphQL\Support\Transformer\Eloquent\DropTransformer::class,
		'store' => \StudioNet\GraphQL\Support\Transformer\Eloquent\StoreTransformer::class,
		'batch' => \StudioNet\GraphQL\Support\Transformer\Eloquent\BatchTransformer::class,
		'restore' => \StudioNet\GraphQL\Support\Transformer\Eloquent\RestoreTransformer::class,
	];

	/**
	 * Return wanted transformers
	 *
	 * @return array
	 */
	public function getTransformers() {
		return [
			'list' => true,
			'view' => true,
			'drop' => true,
			'store' => true,
			'batch' => true,
			'restore' => in_array(SoftDeletes::class, class_uses($this->getSource()))
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
				'type' => Type::bool(),
				'description' => 'Is model deleted ?',
				'inputable' => false,
				'resolve' => function ($root) { return $root->trashed(); }
			];
			$fields['deleted_at'] = [
				'type' => Type::datetime(),
				'description' => 'When was model deleted',
				'inputable' => false,
				'resolve' => function ($root) { return $root->deleted_at; }
			];
		}

		return $fields;
	}
}
