<?php

/**
* Class
*/
class TermAggTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->expected_agg = [
			'foobar' => [
				'terms' => [
					'field' => 'foobar'
				]
			]
		];	

		$this->agg = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs\TermAgg');
	}

	public function testMakeReturnsExpectedAgg()
	{
		$params = [
			'field' => 'foobar',
		];

		$result = $this->agg->make($params);

		assertThat($result, identicalTo($this->expected_agg));
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionIfFieldParamIsMissing()
	{
		$params = [
			'field' => ''
		];

		$this->agg->make($params);
	}
}