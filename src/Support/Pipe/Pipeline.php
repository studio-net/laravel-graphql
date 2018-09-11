<?php
namespace StudioNet\GraphQL\Support\Pipe;

use Illuminate\Pipeline\Pipeline as PipelineBase;

/**
 * Pipeline
 *
 * @see PipelineBase
 */
class Pipeline extends PipelineBase {
	/** @var array $parameters */
	protected $parameters = [];

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @override
	 * @return \Closure
	 */
	protected function carry() {
		return function ($stack, $pipe) {
			return function ($passable) use ($stack, $pipe) {
				if (is_callable($pipe)) {
					// If the pipe is an instance of a Closure, we will just call it directly but
					// otherwise we'll resolve the pipes out of the container and call it with
					// the appropriate method and arguments, returning the results back out.
					return $pipe($passable, $stack, ...$this->parameters);
				} elseif (!is_object($pipe)) {
					list($name, $parameters) = $this->parsePipeString($pipe);

					// If the pipe is a string we will parse the string and resolve the class out
					// of the dependency injection container. We can then build a callable and
					// execute the pipe function giving in the parameters that are required.
					$pipe = $this->getContainer()->make($name);
					$parameters = array_merge([$passable, $stack], array_merge($this->parameters, $parameters));
				} else {
					// If the pipe is already an object we'll just make a callable and pass it to
					// the pipe as-is. There is no need to do any extra parsing and formatting
					// since the object we're given was already a fully instantiated object.
					$parameters = array_merge([$passable, $stack], $this->parameters);
				}

				return method_exists($pipe, $this->method) ? $pipe->{$this->method}(...$parameters) : $pipe(...$parameters);
			};
		};
	}

	/**
	 * with
	 *
	 * @param  mixed $params,...
	 * @return self
	 */
	public function with(...$params): self {
		$this->parameters = $params;
		return $this;
	}
}
