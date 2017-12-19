<?php
namespace StudioNet\GraphQL\Error;

use GraphQL\Error\Error;
use Illuminate\Validation\Validator;

/**
 * ValidationError
 *
 * @see Error
 */
class ValidationError extends Error {
	/** @var Validator $validator */
	private $validator;

	/**
	 * setValidator
	 *
	 * @param  Validator $validator
	 * @return void
	 */
	public function setValidator(Validator $validator) {
		$this->validator = $validator;

		return $this;
	}

	/**
	 * Return validator messages
	 *
	 * @return array
	 */
	public function getValidatorMessages() {
		return ($this->validator) ? $this->validator->messages() : [];
	}
}
