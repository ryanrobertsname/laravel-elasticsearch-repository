<?php

/**
* Class
*/
class NumberRangeFilterTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->config = [
			//for migrations and validation of available fields
			'setup' => [
				//index name (specified here so different environments can use specifc names)
				'index' => 'testing',

				//type name (specified here so different environments can use specifc names)
				'type' => 'test',

				//for mapping
				'mapping' => [
					'product_id' => 'integer',
					'datetime' => 'date',
					'title' => 'string',
					'descriptions' => 'string',
					'features' => 'string',
					'binding' => 'string',
					'brand' => 'string',
					'manufacturer' => 'string',
					'model' => 'string',
					'group' => 'string',
					'size' => 'string',
					'clothing_size' => 'string',
					'occasions' => 'string',
					'min_price' => 'integer',
					'max_price' => 'integer'
				]
			]
		];

	}
		
	public function testMakeReturnsExpectedFilter()
	{
		$filter_type = 'NumberRangeFilter';
		$filter_params = [
			'field' => 'min_price',
			'min' => 0,
			'max' => 100
		];

		//$expected_response_in_json = '{"range":{"price":{"gte":0,"lte":100}}}';

		$expected_response = [
			'range' => [
				'min_price' => [
					'gte' => 0,
					'lte' => 100
				]
			]
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params, $this->config);

		assertThat($response, identicalTo($expected_response));
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenFieldParamIsMissing()
	{
		$filter_type = 'NumberRangeFilter';
		$filter_params = [
			'min' => 0,
			'max' => 100
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params, $this->config);
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenMinAndMaxParamsAreMissing()
	{
		$filter_type = 'NumberRangeFilter';
		$filter_params = [
			'field' => 'min_price'
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params, $this->config);
	}
	
	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenMinIsNotAnInteger()
	{
		$filter_type = 'NumberRangeFilter';
		$filter_params = [
			'field' => 'min_price',
			'min' => 'test',
			'max' => 100
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params, $this->config);
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testInvalidArgumentExceptionIsThrownWhenMaxIsNotAnInteger()
	{
		$filter_type = 'NumberRangeFilter';
		$filter_params = [
			'field' => 'min_price',
			'min' => 100,
			'max' => 'test'
		];

		$filter = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_type);
		$response = $filter->make($filter_params, $this->config);
	}
}