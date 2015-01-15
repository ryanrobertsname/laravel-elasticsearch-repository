<?php

/**
* Class
*/
class NumberRangeAggTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->expected_agg = [
			'foobar'.'_histogram' => [
				'histogram' => [
					'field' => 'foobar',
					'interval' => 22,
				]
			],
			'foobar'.'_stats' => [
				'extended_stats' => [
					'field' => 'foobar'
				]
			]
		];	

		$this->agg = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs\NumberRangeAgg');
	}

	public function testMakeReturnsExpectedAgg()
	{
		$params = [
			'field' => 'foobar',
			'interval' => 22
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
			'field' => '',
			'interval' => 22
		];

		$this->agg->make($params);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionIfIntervalParamIsMissing()
	{
		$params = [
			'field' => 'foobar',
			'interval' => ''
		];

		$this->agg->make($params);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionIfFieldParamIsNotInteger()
	{
		$params = [
			'field' => 'foobar',
			'interval' => 'one'
		];

		$this->agg->make($params);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionIfFieldParamIsZero()
	{
		$params = [
			'field' => 'foobar',
			'interval' => 0
		];

		$this->agg->make($params);
	}

}