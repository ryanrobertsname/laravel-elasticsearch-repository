<?php

/**
* Class
*/
class ESSearchParamsGeneratorTest extends TestCase
{
	protected $price_range_filter_mock;

	public function setUp()
	{
		parent::setUp();

		$this->expected_search_params = [
			'index' => 'testing',
			'type' => 'test',
			'body' => [
				'from' => 0,
				'size' => 100,
				'_source' => false,
				'query' => [
					'filtered' => [
						'query' => [
							'query_string' => [
								'query' => 'test query~1',
								'fields' => [
									'title^1', 'descriptions^2', 'features^3', 'binding^4', 'brand^5', 'manufacturer^6', 'model^7', 'group^8', 'size^9',
									'clothing_size^10', 'occasions^11'
								]
							]
						]
					]
				],
				'aggs' => [
					'prices' => [
						'histogram' => [
							'field' => 'min_price',
							'interval' => 25
						]
					],
					'price_stats' => [
						'extended_stats' => [
							'field' => 'min_price'
						]
					]
				]
			]
		];

		$this->expected_search_params_with_filters = [
			'index' => 'testing',
			'type' => 'test',
			'body' => [
				'from' => 0,
				'size' => 100,
				'_source' => false,
				'query' => [
					'filtered' => [
						'query' => [
							'query_string' => [
								'query' => 'test query~1',
								'fields' => [
									'title^1', 'descriptions^2', 'features^3', 'binding^4', 'brand^5', 'manufacturer^6', 'model^7', 'group^8', 'size^9',
									'clothing_size^10', 'occasions^11'
								]
							]
						],
						'filter' => [
							'bool' => [
								'must' => [
									[
										'or' => [
											[
												'test filter' => 1
											],
											[
												'test filter' => 2
											]
										]
									],
									[
										'or' => [
											[
												'test filter' => 3
											],
											[
												'test filter' => 4
											]
										]
									]
								]
							]
						]
					]
				],
				'aggs' => [
					'prices' => [
						'histogram' => [
							'field' => 'min_price',
							'interval' => 25
						]
					],
					'price_stats' => [
						'extended_stats' => [
							'field' => 'min_price'
						]
					]
				]
			]
		];


		$this->config = [
			//for migrations and validation of available fields
			'setup' => [
				//index name (specified here so different environments can use specifc names)
				'index' => 'testing',

				//type name (specified here so different environments can use specifc names)
				'type' => 'test',

				//for mapping
				'mapping' => [
					'properties' => [
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
			],

			//for indexing operations
			'index' => [
				'field_rules' => [
					'product_id' => 'required|integer',
					'datetime' => 'required|date',
					'title' => 'required',
					'min_price' => 'required|integer',
					'max_price' => 'required|integer'
				],
				'parent_type' => null
			],

			//for deleting operations
			'delete' => [

			],

			//for searching operations
			'search' => [
				//search type
				'product_search' => [
					'query' => [
						'type' => 'string',
						'params' => [
							'append' => '~1',
							'fields' => [
								'title' => '^1', 'descriptions' => '^2', 'features' => '^3', 'binding' => '^4', 'brand' => '^5', 'manufacturer' => '^6', 'model' => '^7', 'group' => '^8', 'size' => '^9',
								'clothing_size' => '^10', 'occasions' => '^11'
							],
						]
					],
					'aggs' => [
						'price' => [  //user defined name for agg
							'agg' => 'NumberRangeAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'min_price',
								'interval' => 1000,
								'max_aggs' => 6  //compression will take place if more than max exist
							]
						]
					],
					'filters' => [

					]
				]
			]

		];

		$this->setNumberRangeAggMock();

	}

	protected function setQueryGenMock()
	{
		$query_gen = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\QueryGeneratorInterface');
		$query_gen->shouldReceive('make')->once()->with('test query', $this->config['search']['product_search'])->andReturn(
			[
				'query_string' => [
					'query' => 'test query~1',
					'fields' => [
						'title^1', 'descriptions^2', 'features^3', 'binding^4', 'brand^5', 'manufacturer^6', 'model^7', 'group^8', 'size^9',
						'clothing_size^10', 'occasions^11'
					]
				]
			]
		);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\StringQueryGenerator', $query_gen);
	}

	protected function setConfigMock()
	{
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->andReturn($this->config);		
	}

	protected function setNumberRangeAggMock()
	{
		$number_range_agg = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs\NumberRangeAgg');
		$number_range_agg->shouldReceive('make')->andReturn(
			[
				'prices' => [
					'histogram' => [
						'field' => 'min_price',
						'interval' => 25
					]
				],
				'price_stats' => [
					'extended_stats' => [
						'field' => 'min_price'
					]
				]
			]
		);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs\NumberRangeAgg', $number_range_agg);
	}

	public function testMakeParamsReturnsExpectedParams()
	{
		$this->setConfigMock();
		$this->setQueryGenMock();
		
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100);

		assertThat($result, identicalTo($this->expected_search_params));
	}

	public function testMakeParamsReturnsExpectedParamsWhenFieldsAreSelected()
	{
		$this->setConfigMock();
		$fields = ['product_id', 'title'];

		$this->setQueryGenMock();

		$this->expected_search_params['body']['_source'] = $fields;

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100, $fields, []);

		assertThat($result, identicalTo($this->expected_search_params));
	}

	// public function testMakeParamsAddsFilterAppropriately()
	// {		
	// 	$this->setConfigMock();

	// 	$this->setQueryGenMock();

	// 	$number_range_filter_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\NumberRangeFilter');
	// 	$number_range_filter_mock->shouldReceive('make')->once()->with(['field' => 'min_price', 'min' => 1])->andReturn(['test filter' => 1]);
	// 	$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\NumberRangeFilter', $number_range_filter_mock);

	// 	$filter = [
	// 		'price' => [
	// 			'filter' => 'NumberRangeFilter',
	// 			'params' => [
	// 				'field' => 'min_price', 'min' => 1
	// 			]
	// 		]
	// 	];

	// 	$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
	// 	$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100, [], $filter);

	// 	assertThat($result, identicalTo($this->expected_search_params_with_filters));
	// }

	public function testMakeParamsAddsFilterAppropriately()
	{		
		$this->setConfigMock();

		$this->setQueryGenMock();

		$number_range_filter_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\NumberRangeFilter');
		$number_range_filter_mock->shouldReceive('make')->once()->with(['field' => 'min_price', 'min' => 1])->andReturn(['test filter' => 1]);
		$number_range_filter_mock->shouldReceive('make')->once()->with(['field' => 'min_price', 'min' => 2])->andReturn(['test filter' => 2]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\NumberRangeFilter', $number_range_filter_mock);

		$term_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\TermFilter');
		$term_mock->shouldReceive('make')->once()->with(['field' => 'group', 'value' => 'foobar'])->andReturn(['test filter' => 3]);
		$term_mock->shouldReceive('make')->once()->with(['field' => 'group', 'value' => 'foobar2'])->andReturn(['test filter' => 4]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\TermFilter', $term_mock);

		$filters = [
			[
				'filter' => 'NumberRangeFilter',
				'like_filter_relation_type' => 'and',
				'params' => [
					'field' => 'min_price', 'min' => 1
				]
			],
			[
				'filter' => 'NumberRangeFilter',
				'like_filter_relation_type' => 'and',
				'params' => [
					'field' => 'min_price', 'min' => 2
				]
			],
			[
				'filter' => 'TermFilter',
				'like_filter_relation_type' => 'or',
				'params' => [
					'field' => 'group', 'value' => 'foobar'
				]
			],
			[
				'filter' => 'TermFilter',
				'like_filter_relation_type' => 'or',
				'params' => [
					'field' => 'group', 'value' => 'foobar2'
				]
			] 
		];

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100, [], $filters);

		assertThat($result, identicalTo($this->expected_search_params_with_filters));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionWhenFilterFieldIsInvalid()
	{	
		$this->setConfigMock();

		$this->setQueryGenMock();

		$filter = [
			'price' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'foobar', 'min' => 1
				]
			]
		];

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100, [], $filter);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionWhenAggFieldIsInvalid()
	{	
		$this->config['search']['product_search']['aggs']['price']['params']['field'] = 'foobar';  //invalid
		$this->setConfigMock();
		$this->setQueryGenMock();

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100);
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testMakeParamsThrowsInvalidArgumentExceptionWhenOffsetIsNotAnInteger()
	{
		$this->setConfigMock();

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'query', 'one', 100);
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testMakeParamsThrowsInvalidArgumentExceptionWhenLimitIsNotAnInteger()
	{
		$this->setConfigMock();

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'query', 0, 'five');
	}


	public function testMakeParamsWorksWithZeroLimit()
	{
		$this->setConfigMock();

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'query', 0, 0);

		assertThat($result, equalTo(true));
	}

	/**
     * @expectedException InvalidArgumentException
     */
	public function testMakeParamsThrowsInvalidArgumentExceptionWhenSearchParamFieldValidationFails()
	{
		$this->setConfigMock();

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$result = $generator->makeParams('product', 'product_search', 'test query', 0, 100, ['foo', 'bar'], []);
	}
	
}