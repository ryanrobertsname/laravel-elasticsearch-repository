<?php

class IndexRepositoryFunctionalTest extends TestCase
{
	protected $repo;
	protected $es_client;
	protected $product_index;
	protected $product_type;
	protected $add_index;
	protected $add_type;

	public function setUp()
	{
		parent::setUp();

		$this->es_client = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$this->repo = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');
		$this->product_index = 'laravel-elasticsearch-repository-testing-index';
		$this->product_type = 'product';
		$this->add_index = $this->product_index;  //add and product are in the same index
		$this->add_type = 'add';

		//just in case index is left over from a test that didnt properly close
		$this->deleteIndexIfExists($this->product_index);

		$this->migrateIndex();

		$this->makeConfigMocks();
	}
	
	public function tearDown()
	{
		$this->deleteIndexIfExists($this->product_index);

		parent::tearDown();
	}

	protected function doesIndexExist($index)
	{
		return $this->es_client->indices()->exists([
			'index' => $index
		]);
	}

	protected function deleteIndexIfExists($index)
	{
		if($this->doesIndexExist($index))		
			$this->es_client->indices()->delete([
				'index' => $index
			]);
	}

	protected function migrateIndex()
	{		
		try {

			$this->es_client->indices()->create(['index' => $this->product_index]);

			$this->mapProduct();

			$this->mapAdd();

		}
		catch (\Exception $e)
		{
			var_dump($e->getMessage());
		}	
	}

	protected function mapProduct()
	{
		$params['index'] = $this->product_index;
		$params['type']  = $this->product_type;

		$product_fields = [
			'soft_delete_status' => 'integer',
			'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],
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
			'keyword_profile' => 'string'
		];

		foreach ($product_fields as $field => $map_type)
			if (is_array($map_type)):
				$mapping_fields[$field] = $map_type;
			else:
				$mapping_fields[$field] = ['type' => $map_type];
			endif;

		$type_mappings = array(
		    'properties' => $mapping_fields
		);

		$params['body'][$this->product_type] = $type_mappings;

		$this->es_client->indices()->putMapping($params);
	}

	protected function mapAdd()
	{
		$params['index'] = $this->add_index;
		$params['type']  = $this->add_type;

		$add_fields = [
			'occasion_ids' => 'integer',
			'relation_ids' => 'integer',
			'gender_ids' => 'integer',
			'interest_ids' => 'integer',
			'sub_interest_ids' => 'integer',
			'age_ids' => 'integer',
			'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],
			'add_type_id' => 'integer'
		];

		foreach ($add_fields as $field => $map_type)
			if (is_array($map_type)):
				$mapping_fields[$field] = $map_type;
			else:
				$mapping_fields[$field] = ['type' => $map_type];
			endif;

		$type_mappings = array(
		    'properties' => $mapping_fields
		);

		//config parent
		$type_mappings['_parent']['type'] = $this->product_type;

		$params['body'][$this->add_type] = $type_mappings;

		$this->es_client->indices()->putMapping($params);
	}
	
	protected function makeConfigMocks(array $overrides = [])
	{
		$stubs = [
			'laravel-elasticsearch-repository::index.index_models.product' => [  

				//for migrations and validation of available fields
				'setup' => [
					//index name (specified here so different environments can use specifc names)
					'index' => $this->product_index,

					//type name (specified here so different environments can use specifc names)
					'type' => $this->product_type,

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
					'index' => $this->add_index,

					//type name (specified here so different environments can use specifc names)
					'type' => $this->add_type,

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
						'parent' => $this->product_type
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
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')
			->andReturn(true);

		//mock config for "product_add" index model
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')
			->andReturn($stubs['laravel-elasticsearch-repository::index.index_models.product_add']);
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product_add')
			->andReturn(true);
	}

	public function testTest()
	{
		assertThat(true, equalTo(true));
	}
	
	public function testIndexReturnsTrueOnSuccessAndIndexesDoc()
	{	
		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		$result = $this->repo->model('product')->index([
			'_id' => 100,
			'title' => 'test product',
			'min_price' => 20000,
			'max_price' => 20000,
			'datetime' => '2014-02-14 00:01:01',
			'soft_delete_status' => 0
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], not(emptyArray()));
		assertThat($findings['meta']['total_hits'], equalTo(1));
	}

	public function testUpdateIndexReturnsTrueOnSuccess()
	{	
		$result = $this->repo->model('product')->index([
			'_id' => 100,
			'title' => 'test product',
			'min_price' => 20000,
			'max_price' => 20000,
			'datetime' => '2014-02-14 00:01:01',
			'soft_delete_status' => 0
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100, [], ['title', 'min_price']);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], not(emptyArray()));
		assertThat($findings['results'][0]['_source']['title'], identicalTo('test product'));
		assertThat($findings['results'][0]['_source']['min_price'], identicalTo(20000));
		assertThat($findings['meta']['total_hits'], equalTo(1));

		$result = $this->repo->model('product')->updateIndex([
			'_id' => 100,
			'title' => 'test product 2',
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100, [], ['title', 'min_price']);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], not(emptyArray()));
		assertThat($findings['results'][0]['_source']['title'], identicalTo('test product 2'));
		assertThat($findings['results'][0]['_source']['min_price'], identicalTo(20000));
		assertThat($findings['meta']['total_hits'], equalTo(1));
	}

	public function testIndexReturnsTrueOnSuccessAndIndexesDocWithSoftDelete()
	{	
		$result = $this->repo->model('product')->index([
			'_id' => 100,
			'title' => 'test product',
			'min_price' => 20000,
			'max_price' => 20000,
			'datetime' => '2014-02-14 00:01:01',
			'soft_delete_status' => 1
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], emptyArray());
	}

	public function testBulkIndexReturnsTrueOnSuccessAndIndexesDoc()
	{	
		$result = $this->repo->model('product')->bulkIndex([
			[
				'_id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			[
				'_id' => 200,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			]
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], not(emptyArray()));
		assertThat($findings['meta']['total_hits'], equalTo(2));
	}

	public function testBulkIndexReturnsTrueOnSuccessAndIndexesDocsWithSoftDelete()
	{	
		$result = $this->repo->model('product')->bulkIndex([
			[
				'_id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 1
			],
			[
				'_id' => 200,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			]
		], true);

		$findings = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		assertThat($result, identicalTo(true));
		assertThat($findings, hasKey('results'));
		assertThat($findings['results'], not(emptyArray()));
		assertThat($findings['meta']['total_hits'], equalTo(1));
	}

	public function testDeleteReturnsTrueOnSuccessAndDeletesDoc()
	{
		$doc_id = 100;

		$this->repo->model('product')->index([
			'_id' => $doc_id,
			'title' => 'test product',
			'min_price' => 20000,
			'max_price' => 20000,
			'datetime' => '2014-02-14 00:01:01',
			'soft_delete_status' => 0
		], true);   //true to refresh

		$result = $this->repo->model('product')->delete($doc_id, true);

		assertThat($result, identicalTo(true));

		$result = $this->repo->model('product')->search('product_search', 'test product', 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], emptyArray());
	}

	public function testDeleteProductAndAddsReturnsTrueOnSuccessAndDeletesDoc()
	{
		$doc_id = 100;

		$this->repo->model('product')->index([
			'_id' => $doc_id,
			'title' => 'test product',
			'min_price' => 20000,
			'max_price' => 20000,
			'datetime' => '2014-02-14 00:01:01',
			'soft_delete_status' => 0
		], true);  //true to refreh index

		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 1,
			'parent' => $doc_id,
			'body' => [
				'gender_id' => 1
			],
			'refresh' => true
		]);

		//assert that product is indexed
		$result = $this->repo->model('product')->search('product_search', 'test product', 0, 100);
		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));

		//assert that add is indexed
		$result = $this->es_client->search([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'body' => [
				'query' => [
					'filtered' => [
						'filter' => [
							'type' => [
								'value' => $this->add_type
							]
						]
					]
				]
			]
		]);
		assertThat($result['hits']['total'], greaterThan(0));

		//delete product and adds
		$result = $this->repo->model('product')->deleteSelfAndChildren($doc_id, ['product_add']);  //true to refresh index

		//assert that product is no longer indexed
		$result = $this->repo->model('product')->search('product_search', 'test product', 0, 100);
		assertThat($result, hasKey('results'));
		assertThat($result['results'], emptyArray());

		//assert that add is no longer indexed
		$result = $this->es_client->search([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'body' => [
				'query' => [
					'filtered' => [
						'filter' => [
							'type' => [
								'value' => $this->add_type
							]
						]
					]
				]
			]
		]);
		assertThat($result['hits']['total'], equalTo(0));
	}

	/**
	 * @expectedException RunTimeException
	 */
	public function testDeleteProductThrowsRunTimeExceptionOnFailure()
	{
		$es_client_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
		$es_client_mock->shouldReceive('deleteByQuery')->once()->andReturn([]);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', $es_client_mock);

		$doc_id = 100;

		$repo = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');
		$result = $repo->deleteSelfAndChildren($doc_id, ['product_add']);

		assertThat($result, identicalTo(false));
	}

	public function testKeywordSearchReturnsResultOnSuccess()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('product_search', 'test', 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], emptyArray());
		assertThat($result['facets'], hasKey('group'));
		assertThat($result['meta']['total_hits'], equalTo(1));
	}

	public function testKeywordSearchReturnsExpectedFacets()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product2',
				'min_price' => 30000,
				'max_price' => 30000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('product_search', 'test', 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], equalTo(2));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], not(emptyArray()));

	}

	public function testKeywordSearchReturnsResultOnSuccessWithLimit()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('product_search', 'test', 0, 1);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'], hasKey('_id'));
		assertThat($result['results'][0]['_id'], equalTo(100));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], equalTo(2));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], emptyArray());

	}

	public function testKeywordSearchReturnsResultOnSuccessWithOffset()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('product_search', 'test', 1, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'], hasKey('_id'));
		assertThat($result['results'][0]['_id'], equalTo(200));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], equalTo(2));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], emptyArray());

	}

	public function testKeywordSearchReturnsResultOnSuccessWithFields()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('product_search', 'test', 0, 100, [], ['title', 'min_price']);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], hasKey('_source'));
		assertThat($result['results'][0]['_source'], hasKey('title'));
		assertThat($result['results'][0]['_source'], hasKey('min_price'));
		assertThat($result['meta']['total_hits'], equalTo(1));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], emptyArray());

	}

	public function testKeywordSearchReturnsResultOnSuccessWithFieldsAndFilters()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product 2',
				'min_price' => 40000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$filters = [
			'price' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 30000
				]
			]
		];
		$result = $this->repo->model('product')->search('product_search', 'test', 0, 100, $filters, ['title', 'min_price']);


		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'], not(hasKey(1)));
		assertThat($result['results'][0], hasKey('_source'));
		assertThat($result['results'][0]['_source'], hasKey('title'));
		assertThat($result['results'][0]['_source'], hasKey('min_price'));
		assertThat($result['results'][0]['_source']['title'], equalTo('test product 2'));
		assertThat($result['meta']['total_hits'], equalTo(1));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], emptyArray());
	}

	public function testFilterProducts()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product2',
				'min_price' => 30000,
				'max_price' => 30000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->search('filter_products', null, 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], equalTo(2));
		assertThat($result['facets'], emptyArray());
	}

	public function testMltSearchReturnsResultOnSuccess()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);


		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test product news',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->mltSearch(100, 5, ['min_term_freq' => 1, 'min_doc_freq' => 1]);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['facets'], emptyArray());
		assertThat($result['results'][0], hasKeyValuePair('_id', 200));
		assertThat($result['meta']['total_hits'], equalTo(1));
	}

	public function testMltSearchReturnsResultOnSuccessWithLimit()
	{	
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'id' => 100,
				'title' => 'test',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'id' => 200,
				'title' => 'test',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 300,
			'body' => [
				'id' => 300,
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		$result = $this->repo->model('product')->mltSearch(100, 1, ['min_term_freq' => 1, 'min_doc_freq' => 1]);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'], not(hasKey(1)));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['facets'], emptyArray());
		assertThat($result['results'][0], hasKeyValuePair('_id', 200));
		assertThat($result['meta']['total_hits'], equalTo(2));
	}

	public function testChildPollSearchReturnsResultOnSuccess()
	{	
		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'title' => 'test product2',
				'min_price' => 40000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup2',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 1,
			'parent' => 100,
			'body' => [
				'gender_ids' => 1,
				'interest_ids' => [1, 2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 2,
			'parent' => 100,
			'body' => [
				'gender_ids' => 2,
				'interest_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 3,
			'parent' => 200,
			'body' => [
				'gender_ids' => 1,
				'interest_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		$poll_params = [
			'gender_ids' => 1,
			'interest_ids' => [1, 2]
		];

		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add.setup.type')->andReturn('add');

		$result = $this->repo->model('product')->search('add_poll', $poll_params, 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], atLeast(1));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], not(emptyArray()));
		assertThat($result['facets'], hasKey('group'));
	}

	public function testAddAnalyticsReturnsResultOnSuccess()
	{	
		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'title' => 'test product2',
				'min_price' => 40000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup2',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 1,
			'parent' => 100,
			'body' => [
				'gender_ids' => 1,
				'interest_ids' => [1, 2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 2,
			'parent' => 100,
			'body' => [
				'gender_ids' => 2,
				'interest_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 3,
			'parent' => 200,
			'body' => [
				'gender_ids' => 1,
				'interest_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		$poll_params = [
			'must' => [
				'gender_ids' => 1,
				'interest_ids' => 2
			]
		];

		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add.setup.type')->andReturn('add');

		$result = $this->repo->model('product')->search('add_hits', $poll_params, 0, 100);

		assertThat($result, hasKey('meta'));
		assertThat($result['meta']['total_hits'], atLeast(1));
	}

	public function testAddPopularitySearchReturnsResultOnSuccess()
	{	
		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 100,
			'body' => [
				'title' => 'test product',
				'min_price' => 20000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup1',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//product
		$this->es_client->index([
			'index' => $this->product_index,
			'type' => $this->product_type,
			'id' => 200,
			'body' => [
				'title' => 'test product2',
				'min_price' => 30000,
				'max_price' => 20000,
				'datetime' => '2014-02-14 00:01:01',
				'group' => 'testgroup2',
				'soft_delete_status' => 0
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 1,
			'parent' => 100,
			'body' => [
				'gender_ids' => 1,
				'interest_ids' => [1, 2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 2,
			'parent' => 100,
			'body' => [
				'gender_ids' => 2,
				'interest_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		//add
		$this->es_client->index([
			'index' => $this->add_index,
			'type' => $this->add_type,
			'id' => 3,
			'parent' => 200,
			'body' => [
				'gender_ids' => 1,
				'interests\_ids' => [2],
				'datetime' => '2014-02-14 00:01:01'
			],
			'refresh' => true
		]);

		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add.setup.type')->andReturn('add');

		$result = $this->repo->model('product')->search('add_count', null, 0, 100);

		assertThat($result, hasKey('results'));
		assertThat($result['results'], not(emptyArray()));
		assertThat($result['results'][0], not(hasKey('_source')));
		assertThat($result['meta']['total_hits'], atLeast(1));
		assertThat($result, hasKey('facets'));
		assertThat($result['facets'], hasKey('price'));
		assertThat($result['facets']['price'], not(emptyArray()));
		assertThat($result['facets'], hasKey('group'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testKeywordSearchThrowsInvalidArgExceptionIfQueryStringLengthIsGreaterThan100()
	{	
		$query = str_pad('a', 101);
		$result = $this->repo->model('product')->search('product_search', $query, 0, 100);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testKeywordSearchThrowsInvalidArgExceptionWhenNumberOfFiltersExceeds200()
	{	
		$filters = array_pad([1], 201, 1);  //invalid, max number of filters is 200
		$result = $this->repo->model('product')->search('product_search', 'test', 0, 100, $filters);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testAddPopularitySearchThrowsInvalidArgExceptionWhenNumberOfFiltersExceeds200()
	{	
		$filters = array_pad([1], 201, 1);  //invalid, max number of filters is 200
		$result = $this->repo->model('product')->search('add_count', null, 0, 100, $filters);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testChildPollSearchThrowsInvalidArgExceptionWhenNumberOfFiltersExceeds200()
	{	
		$filters = array_pad([1], 201, 1);  //invalid, max number of filters is 200
		$result = $this->repo->model('product')->search('add_poll', ['test'], 0, 100, $filters);
	}


	///////////
	

	public function testIndexReturnsTrueOnSuccessAndMakesAddAProductChild()
	{	
		$doc_id = 100;

		//create parent product
		$this->es_client->create(
			[
				'index' => $this->product_index,
				'type' => $this->product_type,
				'id' => 1,
				'body' => [
					'title' => 'foobar'
				]
			]
		);

		//create add child using repo
		$create_result = $this->repo->model('product_add')->index([
			'_id' => $doc_id,
			'gender_ids' => 1,
			'interest_ids' => [100, 200],
			'occasion_ids' => 1,
			'relation_ids' => 1,
			'age_ids' => 1,
			'sub_interest_ids' => [101],
			'datetime' => '2014-02-14 00:01:01',
			'add_type_id' => 1,
			'_parent_id' => 1
		], true);  //true to refresh index for test

		//check that add child exists and is connected to parent
		$check_result = $this->es_client->get(
			[
				'index' => $this->add_index,
				'type' => $this->add_type,
				'parent' => 1,
				'id' => $doc_id
			]
		);

		assertThat($create_result, identicalTo(true));
		assertThat($check_result['found'], identicalTo(true));
	}

	public function testBulkIndexReturnsTrueOnSuccessAndMakesAddAProductChild()
	{
		$doc_id = 100;

		//create parent product
		$this->es_client->create(
			[
				'index' => $this->product_index,
				'type' => $this->product_type,
				'id' => 1,
				'body' => [
					'title' => 'foobar'
				]
			]
		);

		//create add child using repo
		$create_result = $this->repo->model('product_add')->bulkIndex([
			[
				'_id' => $doc_id,
				'gender_ids' => 1,
				'interest_ids' => [100, 200],
				'occasion_ids' => 1,

				'relation_ids' => 1,
				'age_ids' => 1,
				'sub_interest_ids' => [101],
				'datetime' => '2014-02-14 00:01:01',
				'add_type_id' => 1,
				'_parent_id' => 1
			]
		], true);  //true to refresh index for test


		//check that add child exists and is connected to parent
		$check_result = $this->es_client->get(
			[
				'index' => $this->add_index,
				'type' => $this->add_type,
				'parent' => 1,
				'id' => $doc_id
			]
		);

		assertThat($create_result, identicalTo(true));
		assertThat($check_result['found'], identicalTo(true));
	}

	public function testDeleteReturnsTrueOnSuccessOfDeletingAdd()
	{
		$doc_id = 100;

		$result = $this->repo->model('product_add')->index([
			'_id' => $doc_id,
			'gender_ids' => 1,
			'interest_ids' => [100, 200],
			'occasion_ids' => 1,
			'relation_ids' => 1,
			'age_ids' => 1,
			'sub_interest_ids' => [101],
			'datetime' => '2014-02-14 00:01:01',
			'add_type_id' => 1,
			'_parent_id' => 1
		]);

		$result = $this->repo->model('product_add')->delete($doc_id);

		assertThat($result, identicalTo(true));
	}

	/**
	 * @expectedException Elasticsearch\Common\Exceptions\Missing404Exception
	 */
	public function testDeleteDeletesAddDoc()
	{
		$doc_id = 100;

		$result = $this->repo->model('product_add')->index([
			'_id' => $doc_id,
			'gender_ids' => 1,
			'interest_ids' => [100, 200],
			'occasion_ids' => 1,
			'relation_ids' => 1,
			'age_ids' => 1,
			'sub_interest_ids' => [101],
			'datetime' => '2014-02-14 00:01:01',
			'add_type_id' => 1,
			'_parent_id' => 1
		], true);  //true to refresh index

		$result = $this->repo->model('product_add')->delete($doc_id, true);  //true to refresh index

		//this should throw an exception since the docuement should not
		//be able to be found
		$this->es_client->get(
			[
				'index' => $this->add_index,
				'type' => $this->add_type,
				'parent' => 1,
				'id' => $doc_id
			]
		);
	}
}