<?php

/**
* Class
*/
class TermFilterTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
	}
		
	public function testMakeReturnsExpectedFilter()
	{
		$filter_type = 'TermFilter';
		$filter_params = [
			'field' => 'testfield',
			'value' => 'testvalue'
		];

		$expected_response = [
			'term' => [
				'testfield' => 'testvalue'
			]
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params);

		assertThat($response, identicalTo($expected_response));
	}

	public function testMakeReturnsExpectedFilterWhenValueIsEmpty()
	{
		$filter_type = 'TermFilter';
		$filter_params = [
			'field' => 'testfield',
			'value' => ''
		];

		$expected_response = [
			'term' => [
				'testfield' => ''
			]
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params);

		assertThat($response, identicalTo($expected_response));
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenFieldParamIsMissing()
	{
		$filter_type = 'TermFilter';
		$filter_params = [
			'value' => 'testvalue',
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params);
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenValueParamAreMissing()
	{
		$filter_type = 'TermFilter';
		$filter_params = [
			'field' => 'testfield'
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params);
	}
}