<?php

/**
* Class
*/
class FilterByChildrenQueryGeneratorTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'none',
				'query' => [
					'bool' => [
						'must' => [
							[
								'bool' => [
									'should' => [
										[
											'term' => [
												'add_type_id' => [
													'value' => 1
												]
											]
										],
										[
											'term' => [
												'add_type_id' => [
													'value' => 2
												]
											]
										]
									]
								]
							],
							[
								'term' => [
									'occasion_ids' => [
										'value' => 1
									]
								]
							]
						],
						'must_not' => [
							[
								'term' => [
									'gender_ids' => [
										'value' => 1
									]
								]
							]
						]
					]
				]	
			]
		];

		$this->query_params = [
			'must' => [
				'add_type_id' => [
					'should' => [1, 2]
				],
				'occasion_ids' => 1
			],
			'must_not' => [
				'gender_ids' => 1
			]
		];

		$this->search_type_config = [
			'query' => [
				'type' => 'FilterByChildren',
				'params' => [
					'has_child_index_model' => 'product_add',
					'fields' => [
						'occasion_ids' => null,
						'relation_ids' => null,
						'gender_ids' => null,
						'interest_ids' => null,
						'sub_interest_ids' => null,
						'age_ids' => null,
						'add_type_id' => null
					]
				]
			],
			'aggs' => [
				'NumberRangeAgg' => [
					'field' => 'min_price'
				]
			],
			'filters' => [

			]
		];
		
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add.setup.type')->andReturn('add');

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\FilterByChildrenQueryGenerator');
	}
	
	public function testMakeReturnsExpectedQuery()
	{
		$result = $this->gen->make($this->query_params, $this->search_type_config);

		assertThat($result, equalTo($this->expected_query));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgWhenQueryParamsIsEmpty()
	{
		$this->gen->make('', $this->search_type_config);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgWhenConfigForChildTypeIsNotSet()
	{
		unset($this->search_type_config['query']['params']['has_child_index_model']);
		$this->gen->make($this->query_params, $this->search_type_config);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgWhenConfigForFieldsIsEmpty()
	{
		$this->search_type_config['query']['params']['fields'] = [];
		$this->gen->make($this->query_params, $this->search_type_config);
	}
}