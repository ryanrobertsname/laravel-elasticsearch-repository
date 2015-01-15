<?php

class IndexRepositoryUnitTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->index_mock_params = [
			'index' => 'index test',
			'type' => 'type test',
			'id' => 100,
			'body' => [
				'field1' => 'test',
				'field2' => 'test'
			]
		];
	}

	protected function getRepo()
	{
		return $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');
	}

	protected function makeConfigMocks(array $overrides = [])
	{
		$stubs = [
			'laravel-elasticsearch-repository::index.index_models.product' => [  

				//for migrations and validation of available fields
				'setup' => [
					//index name (specified here so different environments can use specifc names)
					'index' => 'products',

					//type name (specified here so different environments can use specifc names)
					'type' => 'product',

					//for mapping
					'mapping' => [
						'properties' => [
							'soft_delete_status' => 'integer',
							'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],
							'user_id' => 'integer',
							'title' => 'string',
							'descriptions' => 'string',
							'features' => 'string',
							'binding' => 'string',
							'brand' => 'string',
							'manufacturer' => 'string',
							'model' => 'string',
							'group' => [
								'type' => 'string',
								'index' => 'not_analyzed'
							],
							'size' => 'string',
							'clothing_size' => 'string',
							'min_price' => 'integer',
							'max_price' => 'integer',
							'keyword_profile' => 'string',
							'category_keywords' => 'string'
						],
						'parent' => null
					]
				],

				//for searching operations
				'search' => [
					//search type : product search
					'product_search' => [
						'query' => [
							'type' => 'String',  //query generator that should be used
							'params' => [	//query generator params
								'append' => '~1',
								//fields to search for a query match along with their weights '^3'
								'fields' => [
									'title' => '^5',
									'descriptions' => '^3',
									'features' => '^3',
									'binding' => '^1',
									'brand' => '^2',
									'manufacturer' => '^2',
									'model' => '^2',
									'group' => '^1',
									'size' => '^1',
									'clothing_size' => '^1',
									'keyword_profile' => '^2',
									'category_keywords' => '^1'
								],
							],
						],
						'aggs' => [
							'price' => [  //user defined name for agg
								'agg' => 'NumberRangeAgg', //agg module to use
								'params' => [  //agg module params
									'field' => 'min_price',
									'interval' => 1000,
									'max_aggs' => 4  //compression will take place if more than max exist
								]
							],
							'group' => [  //user defined name for agg
								'agg' => 'TermAgg', //agg module to use
								'params' => [  //agg module params
									'field' => 'group'
								]
							]
						],
						'filters' => [
							[
								'filter' => 'TermFilter',
								'params' => [
									'field' => 'soft_delete_status',
									'value' => 0
								],
								'combine_with_like_instances' => false
							]
						]
					],
					//search type : filter_products
					'filter_products' => [
						'query' => [
							'type' => null,  //query generator that should be used
							'params' => [	//query generator params
								
							],
						],
						'aggs' => [
							
						],
						'filters' => [

						]
					],
					//search type : add poll
					'add_poll' => [
						'query' => [
							'type' => 'Poll', //query generator that should be used
							'params' => [  //query generator params
								'has_child_index_model' => 'product_add',
								'has_child_score_type' => 'sum',
								//similar field value matches and their relevance boost values
								'field_simularity_matches' => [
									'relation_id' => [
										2 => [
											3 => .5
										]
									]
								],
								//allows for cetain field values to have field relevance boost adjustment
								'field_value_boosts' => [
									'relation_id' => [
										4 => 2
									]
								],
								//allows polled items to have overall relevance adjusted based on one or more specific field / value combo
								'poll_type_boosts' => [
									'add_type_id' => [
										2 => 500,  //example, add type = detail view 200x as relevant overall
										1 => 800
									]
								],
								//fields that can be polled along with their boost values
								'fields' => [
									'occasion_ids' => 1,
									'relation_ids' => 1,
									'gender_ids' => 1,
									'interest_ids' => 2,
									'sub_interest_ids' => 4,
									'location_ids' => 1,
									'age_ids' => 1				
								],
							]
						],
						'aggs' => [
							'price' => [  //user defined name for agg
								'agg' => 'NumberRangeAgg', //agg module to use
								'params' => [  //agg module params
									'field' => 'min_price',
									'interval' => 1000,
									'max_aggs' => 4  //compression will take place if more than max exist
								]
							],
							'group' => [  //user defined name for agg
								'agg' => 'TermAgg', //agg module to use
								'params' => [  //agg module params
									'field' => 'group'
								]
							]
						],
						'filters' => [
							[
								'filter' => 'TermFilter',
								'params' => [
									'field' => 'soft_delete_status',
									'value' => 0
								],
								'combine_with_like_instances' => false
							]
						]
					],
					//search type : add count
					'add_count' => [
						'query' => [
							'type' => 'ChildrenCount', //query generator that should be used
							'params' => [  //query generator params
								'has_child_index_model' => 'product_add',
								'has_child_score_type' => 'sum'
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
							],
							'group' => [  //user defined name for agg
								'agg' => 'TermAgg', //agg module to use
								'params' => [  //agg module params
									'field' => 'group'
								]
							]
						],
						'filters' => [
							[
								'filter' => 'TermFilter',
								'params' => [
									'field' => 'soft_delete_status',
									'value' => 0
								],
								'combine_with_like_instances' => false
							]
						]
					],
					//search type : find others most like this
					'mlt_search' => [
						'query' => [
							'type' => 'Mlt', //query generator that should be used
							'params' => [  //query generator params
								'min_term_freq' => 1,
		      					'min_doc_freq' => 1
							]
						],
						'aggs' => [

						],
						'filters' => [
							[
								'filter' => 'TermFilter',
								'params' => [
									'field' => 'soft_delete_status',
									'value' => 0
								],
								'combine_with_like_instances' => false
							]
						]
					],
					//search type : admin add variety analytics (used to poll number of matching docs for analytics)
					'add_hits' => [
						'query' => [
							'type' => 'FilterByChildren', //query generator that should be used
							'params' => [  //query generator params
								'has_child_index_model' => 'product_add',
								//fields that can be filtered, null boost values since we are filtering
								'fields' => [
									'occasion_ids' => null,
									'relation_ids' => null,
									'gender_ids' => null,
									'interest_ids' => null,
									'sub_interest_ids' => null,
									'location_ids' => null,
									'age_ids' => null,
									'add_type_id' => null,
									'user_id' => null		
								],
							],
						],
						'aggs' => [

						],
						'filters' => [
							[
								'filter' => 'TermFilter',
								'params' => [
									'field' => 'soft_delete_status',
									'value' => 0
								],
								'combine_with_like_instances' => false
							]
						]
					]
				]

			],

			'laravel-elasticsearch-repository::index.index_models.product_add' => [ 
				'setup' => [
					//index name (specified here so different environments can use specifc names)
					'index' => 'products',

					//type name (specified here so different environments can use specifc names)
					'type' => 'add',

					//for mapping
					'mapping' => [
						'properties' => [
							'occasion_ids' => 'integer',
							'relation_ids' => 'integer',
							'gender_ids' => 'integer',
							'interest_ids' => 'integer',
							'sub_interest_ids' => 'integer',
							'age_ids' => 'integer',
							'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],
							'add_type_id' => 'integer'
						],
						//parent type
						'parent' => 'product'
					]
				]
			]

		];

		//apply overrides
		if (isset($overrides['laravel-elasticsearch-repository::index.index_models.product']))
			$stubs['laravel-elasticsearch-repository::index.index_models.product'] = $overrides['laravel-elasticsearch-repository::index.index_models.product'];
		if (isset($overrides['laravel-elasticsearch-repository::index.index_models.product_add']))
			$stubs['laravel-elasticsearch-repository::index.index_models.product_add'] = $overrides['laravel-elasticsearch-repository::index.index_models.product_add'];

		//mock config for "product" index model
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')
			->andReturn($stubs['laravel-elasticsearch-repository::index.index_models.product']);

		//mock config for "product_add" index model
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')
			->andReturn($stubs['laravel-elasticsearch-repository::index.index_models.product_add']);
	}

	public function testIndexCallsDependenciesAndReturnsTrueOnSuccess()
	{
		$this->makeConfigMocks();

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('index')->once()
			->with($this->index_mock_params)->andReturn(true);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$params = array_merge($this->index_mock_params['body'],  ['_id' => 100]);

		$index_param_generator_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator');
		$index_param_generator_mock->shouldReceive('makeParams')->with('product', $params)->andReturn($this->index_mock_params);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator', $index_param_generator_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->index($params);

		assertThat($response, identicalTo(true));
	}

	public function testIndexCallsDependenciesAndReturnsTrueOnSuccessWhenRefreshIsRequested()
	{
		$this->makeConfigMocks();

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('index')->once()
			->with(array_merge($this->index_mock_params, ['refresh' => true]))->andReturn(true);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$params = array_merge($this->index_mock_params['body'],  ['_id' => 100]);

		$index_param_generator_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator');
		$index_param_generator_mock->shouldReceive('makeParams')->with('product', $params)->andReturn($this->index_mock_params);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator', $index_param_generator_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->index($params, true);

		assertThat($response, identicalTo(true));
	}

	public function testBatchIndexCallsDependenciesAndReturnsTrueOnSuccess()
	{
		$this->makeConfigMocks();

		$items = [['item']];
		$params_stub = ['params_stub'];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('bulk')->once()
			->with($params_stub)->andReturn(true);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$bulk_index_param_generator_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESBulkIndexParamsGenerator');
		$bulk_index_param_generator_mock->shouldReceive('makeParams')->with('product', $items)->andReturn($params_stub);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESBulkIndexParamsGenerator', $bulk_index_param_generator_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->bulkIndex($items);

		assertThat($response, identicalTo(true));
	}
	
	public function testKeywordSearchCallsDependenciesAndReturnsExpectedDataFormat()
	{
		//input for repository method
		$search_query = 'test query';

		//mocked search params returned by es search params generator
		$mock_search_params = ['mock search params'];

		//mocked response from es client search op
		$mock_es_response = [
			'took' => 36,
			'timed_out' => false,
			'_shards' => [
				'total' => 5,
				'successful' => 5,
				'failed' => 0
			],
			'hits' => [
				'total' => 9,
				'max_scrore' => 1,
				'hits' => [
					[
						'_index' => 'products',
						'_type' => 'product',
						'_id' => 6,
						'_score' => 0.4365,
						'_source' => [
							'title' => 'test title',
							'price' => 200
						]
					],
					[
						'_index' => 'products',
						'_type' => 'product',
						'_id' => 6,
						'_score' => 0.3365,
						'_source' => [
							'title' => 'test title 2',
							'price' => 100
						]
					]
				]
			],
			'aggregations' => [
				'min_price_stats' => [
					'count' => 7,
					'min' => 100,
					'max' => 2000,
					'avg' => 300,
					'sum' => 3450,
					'sum_of_squares' => 4444444,
					'variance' => 34324234,
					'std_deviation' => 631
				],
				'min_price_histogram' => [
					'buckets' => [
						[
							'key' => 100,
							'doc_count' => 5
						],
						[
							'key' => 200,
							'doc_count' => 2
						]
					]
				],
				'group' => [
					'buckets' => [
						[
							'key' => 'fieldvalue1',
							'doc_count' => 5
						],
						[
							'key' => 'fieldvalue2',
							'doc_count' => 2
						]
					]
				]
			]
		];

		//mocked response from price facet generator
		$mock_price_facet_response = [
			['price facets']
		];

		//mocked response from term facet generator
		$mock_term_facet_response = [
			['term facets']
		];

		//expeceted price stats argument provided to price facet generator
		$expected_price_stats_for_facet = [
			'count' => 7,
			'min' => 100,
			'max' => 2000,
			'avg' => 300,
			'sum' => 3450,
			'sum_of_squares' => 4444444,
			'variance' => 34324234,
			'std_deviation' => 631
		];

		//expected price buckets argument provided to price facet generator
		$expected_price_buckets_for_facet = [
			[
				'key' => 100,
				'doc_count' => 5
			],
			[
				'key' => 200,
				'doc_count' => 2
			]
		];

		//expected term buckets argument provided to term facet generator
		$expected_term_buckets_for_facet = [
			[
				'key' => 'fieldvalue1',
				'doc_count' => 5
			],
			[
				'key' => 'fieldvalue2',
				'doc_count' => 2
			]
		];

		//expected response from repo search method
		$expected_response = [
			'meta' => [
				'total_hits' => 9
			],
			'results' => [
				[
					'_index' => 'products',
					'_type' => 'product',
					'_id' => 6,
					'_score' => 0.4365,
					'_source' => [
						'title' => 'test title',
						'price' => 200
					]
				],
				[
					'_index' => 'products',
					'_type' => 'product',
					'_id' => 6,
					'_score' => 0.3365,
					'_source' => [
						'title' => 'test title 2',
						'price' => 100
					]
				]
			],
			'facets' => [
				'price' => [
					['price facets']
				],
				'group' => [
					['term facets']
				]
			]
		];

		$config_stub = [
			'yeah' => 'dude',
			'setup' => [
				'index' => 'products',
				'type' => 'product'
			],
			'search' => [
				'product_search' => [
					'query' => [
						'type' => 'String',  //query generator that should be used
						'params' => [	//query generator params
							'append' => '~1'
						]
					],
					//fields to search for a query match along with their weights '^3'
					'fields' => [
						'title' => '^1', 'descriptions' => '^2', 'features' => '^3', 'binding' => '^4', 'brand' => '^5', 'manufacturer' => '^6', 'model' => '^7', 'group' => '^8', 'size' => '^9',
						'clothing_size' => '^10', 'occasions' => '^11'
					],
					'aggs' => [
						'price' => [  //user defined name for agg
							'agg' => 'NumberRangeAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'min_price',
								'interval' => 1000,
								'max_aggs' => 6  //compression will take place if more than max exist
							]
						],
						'group' => [  //user defined name for agg
							'agg' => 'TermAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'group'
							]
						]
					],
					'filters' => [

					]
				]
			]

		];

		$this->makeConfigMocks(['laravel-elasticsearch-repository::index.index_models.product' => $config_stub]);

		$es_search_params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator');
		$es_search_params_gen_mock->shouldReceive('makeParams')->with('product', 'product_search', $search_query, 0, 100, ['title', 'price'], [])->andReturn($mock_search_params);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator', $es_search_params_gen_mock);

		$es_client_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_client_mock->shouldReceive('search')->with($mock_search_params)->once()->andReturn($mock_es_response);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_client_mock);

		$price_facet_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$price_facet_gen_mock->shouldReceive('make')->with('Price', 'min_price', 6, 1000, $expected_price_stats_for_facet, $expected_price_buckets_for_facet)->andReturn($mock_price_facet_response);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator', $price_facet_gen_mock);

		$term_facet_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator');
		$term_facet_gen_mock->shouldReceive('make')->with('group', $expected_term_buckets_for_facet)->andReturn($mock_term_facet_response);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator', $term_facet_gen_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->search('product_search', $search_query, 0, 100, [], ['title', 'price']);
		
		assertThat($response, identicalTo($expected_response));
	}

	public function testMltSearchCallsDependenciesAndReturnsExpectedDataFormat()
	{		
		//doc id for mlt method
		$id = 100;

		//mocked search params returned by es search params generator
		$expected_client_search_params = [
			'index' => 'products',
			'type' => 'product',
			'id' => $id,
			'search_size' => 5,
			'body' => [
				'_source' => false,
				'filter' => [
					'bool' => [
						'must' => [
							[
								'or' => [
									[
										'term' => [
											'soft_delete_status' => 0
										]
									]
								]
							]
						]
					]
				]
			]
		];

		//mocked response from es client search op
		$mock_es_response = [
			'took' => 36,
			'timed_out' => false,
			'_shards' => [
				'total' => 5,
				'successful' => 5,
				'failed' => 0
			],
			'hits' => [
				'total' => 9,
				'max_scrore' => 1,
				'hits' => [
					[
						'_index' => 'products',
						'_type' => 'product',
						'_id' => 6,
						'_score' => 0.4365
					],
					[
						'_index' => 'products',
						'_type' => 'product',
						'_id' => 5,
						'_score' => 0.3365
					]
				]
			]
		];

		//expected response from repo search method
		$expected_response = [
			'meta' => [
				'total_hits' => 9
			],
			'results' => [
				[
					'_index' => 'products',
					'_type' => 'product',
					'_id' => 6,
					'_score' => 0.4365
				],
				[
					'_index' => 'products',
					'_type' => 'product',
					'_id' => 5,
					'_score' => 0.3365
				]
			],
			'facets' => [

			]
		];

		//stub for config mock
		$config_stub = [
			'setup' => [
				'index' => 'products',
				'type' => 'product'
			],
			'search' => [
				//search type : find other products most like this
				'mlt_search' => [
					'query' => [
						'type' => 'Mlt', //query generator that should be used
					],
					//fields that can be polled along with their boost values
					'fields' => [

					],
					'aggs' => [

					],
					'filters' => [

					]
				]
			]

		];
		$this->makeConfigMocks(['laravel-elasticsearch-repository::index.index_models.product' => $config_stub]);

		$es_client_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_client_mock->shouldReceive('mlt')->with($expected_client_search_params)->once()->andReturn($mock_es_response);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_client_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->mltSearch($id, 5);

		assertThat($response, identicalTo($expected_response));
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testDeleteThrowsRuntimeExceptionIfClientResponseDoesNotContainTheFoundKey()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->andReturn(['foo', 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->once()->andReturn(['_foo' => 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->delete($doc_id);
	}

	public function testDeleteCallsEsClientWithCorrectParams()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->once()->with('product', 100, null)->andReturn(['foo', 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->with(['foo', 'bar'])->once()->andReturn(['found' => true]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->model('product')->delete($doc_id);
	}

	public function testDeleteCallsEsClientWithCorrectParamsWhenRefreshIsRequested()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->once()->with('product', 100, true)->andReturn(['foo', 'bar', 'refresh' => true]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->with(['foo', 'bar', 'refresh' => true])->once()->andReturn(['found' => true]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->delete($doc_id, true);
	}

	public function testDeleteCallsDeleteParamsGeneratorWithCorrectParams()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'],
			'id' => $doc_id
		];

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->with('product', $doc_id, false)->andReturn($client_params_mock);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->once()->andReturn(['found' => true]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->model('product')->delete($doc_id);
	}

	public function testDeleteReturnsTrueIfClientResponseFoundIsTrue()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'],
			'id' => $doc_id
		];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->with($client_params_mock)->once()->andReturn(['found' => true]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->delete($doc_id);

		assertThat($response, identicalTo(true));
	}
	
	public function testDeleteReturnsFalseIfDocWasNotDeleted()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'],
			'id' => $doc_id
		];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('delete')->with($client_params_mock)->once()->andReturn(['found' => false]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->delete($doc_id);

		assertThat($response, identicalTo(false));
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testDeleteProductAndAddsThrowsRuntimeExceptionIfClientResponseDoesNotContainNonEmptyIndicesKey()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->andReturn(['foo', 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('deleteByQuery')->once()->andReturn(['_foo' => 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);
	}

	public function testDeleteProductAndAddsCallsEsClientWithCorrectParams()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->once()->with('product', ['product_add'], 100)->andReturn(['foo', 'bar']);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator', $params_gen_mock);

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('deleteByQuery')->with(['foo', 'bar'])->once()->andReturn(
		[
			'_indices' => [
				'foo' => [
					'_shards' => [
						'failed' => 0
					]
				]
			]
		]
		);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);
	}

	public function testDeleteProductAndAddsCallsDeleteParamsGeneratorWithCorrectParams()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'],
			'id' => $doc_id
		];

		$params_gen_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator');
		$params_gen_mock->shouldReceive('makeParams')->with('product', ['product_add'], $doc_id)->andReturn($client_params_mock);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator', $params_gen_mock);

		$es_response_stub = [
			'_indices' => [
				'foo' => [
					'_shards' => [
						'failed' => 0
					]
				]
			]
		];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('deleteByQuery')->once()->andReturn($es_response_stub);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();

		$repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);
	}

	public function testDeleteProductAndAddsReturnsTrueIfClientResponseFoundIsTrue()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');
		$config_add = \Config::get('laravel-elasticsearch-repository::index.index_models.product_add');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'].','.$config_add['setup']['type'],
			'body' => [
				'query' => [
					'bool' => [
						'should' => [
							[
								'term' => [
									'_parent' => $config['setup']['type'].'#'.$doc_id
								]
							],
							[
								'ids' => [
									'type' => $config['setup']['type'],
									'values' => [
										$doc_id
									]
								]
							]
						]
					]
				]
			]
		];

		$es_response_stub = [
			'_indices' => [
				'foo' => [
					'_shards' => [
						'failed' => 0
					]
				]
			]
		];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('deleteByQuery')->with($client_params_mock)->once()->andReturn($es_response_stub);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);
	}

	/**
	 * @expectedException RunTimeException
	 */	
	public function testDeleteProductAndAddsThrowsRunTimeExceptionIfESFails()
	{
		$this->makeConfigMocks();

		$doc_id = 100;

		$config = \Config::get('laravel-elasticsearch-repository::index.index_models.product');
		$config_add = \Config::get('laravel-elasticsearch-repository::index.index_models.product_add');

		$client_params_mock = [
			'index' => $config['setup']['index'],
			'type' => $config['setup']['type'].','.$config_add['setup']['type'],
			'body' => [
				'query' => [
					'bool' => [
						'should' => [
							[
								'term' => [
									'_parent' => $config['setup']['type'].'#'.$doc_id
								]
							],
							[
								'ids' => [
									'type' => $config['setup']['type'],
									'values' => [
										$doc_id
									]
								]
							]
						]
					]
				]
			]
		];

		$es_response_stub = [
			'_indices' => [
				'foo' => [
					'_shards' => [
						'failed' => 1
					]
				]
			]
		];

		$es_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_mock->shouldReceive('deleteByQuery')->with($client_params_mock)->once()->andReturn($es_response_stub);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_mock);

		$repo = $this->getRepo();
		$response = $repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);

		assertThat($response, identicalTo(false));
	}
}