<?php
namespace StudioNet\GraphQL\Tests\Support\Pipe;

use StudioNet\GraphQL\Support\Pipe\Pipeline;
use StudioNet\GraphQL\Tests\TestCase;

/**
 * PipelineTest
 *
 * @see TestCase
 */
class PipelineTest extends TestCase {
	/**
	 * pipeline_with_predefined_parameters
	 *
	 * @return void
	 * @test
	 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
	 */
	public function pipeline_with_predefined_parameters() {
		(new Pipeline($this->app))
			->send('my_string')
			->with('test')
			->through([
				function ($value, \Closure $next, $arg) {
					return $next($value . $arg);
				},
				function ($value, \Closure $next, $arg) {
					return $next($value . $arg);
				},
			])
			->then(function ($value) {
				$this->assertEquals('my_stringtesttest', $value);
			});
	}
}
