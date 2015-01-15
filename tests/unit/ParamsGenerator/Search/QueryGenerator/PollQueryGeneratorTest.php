<?php

/**
* Class
*/
class PollQueryGeneratorTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'sum',
				'query' => [
	                'function_score' => [
	                	'functions' => [
							[
								'filter' => [
									'term' => [
										'add_type' => 'detail_view'
									]
								],
								'boost_factor' => 1.2
							]
						],
						'query' => [
							'bool' => [
								'should' => [
									[
										'term' => [
											'interests' => [
												'value' => 'home',
												'boost' => 2
											]
										]
									],
									[
										'term' => [
											'interests' => [
												'value' => 'cars',
												'boost' => 2
											]
										]
									],
									[
										'term' => [
											'gender' => [
												'value' => 'male',
												'boost' => 1
											]
										]
									]
								]
							]
						]
					]
				]	
			]
		];

		$this->query_params = [
			'interests' => [
				'home',
				'cars'
			],
			'gender' => 'male'
		];

		/**
		 * Note that this config is not identical to actual production config, text values where used
		 * instead of value ids for readability
		 */
		$this->search_type_config = [
			'query' => [
				'type' => 'poll',
				'params' => [
					'has_child_index_model' => 'product_add',
					'has_child_score_type' => 'sum',
					'poll_type_boosts' => [
						'add_type' => [
							'detail_view' => 1.2
						]
					],
					'field_value_boosts' => [
						'occasion' => [
							'anniversary' => 1.5,
							'birthday' => 2
						]
					],
					'field_simularity_matches' => [
						'relation' => [
							'parent' => [
								'grandparent' => 0.5
							]
						],
						'occasion' => [
							'birthday' => [
								'anniversary' => 0.5
							]
						]
					],
					'fields' => [
						'occasion' => 2,
						'relation' => 2,
						'gender' => 1,
						'interests' => 2,
						'sub_interests' => 4,
						'location' => 1,
						'age' => 1
					],
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

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\PollQueryGenerator');
	}
	
	public function testMakeReturnsExpectedQuery()
	{
		$result = $this->gen->make($this->query_params, $this->search_type_config);

		assertThat($result, equalTo($this->expected_query));
	}

	public function testMakeReturnsExpectedQueryWithoutPollTypeBoosts()
	{
		unset($this->search_type_config['query']['params']['poll_type_boosts']);

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'sum',
				'query' => [
					'bool' => [
						'should' => [
							[
								'term' => [
									'interests' => [
										'value' => 'home',
										'boost' => 2
									]
								]
							],
							[
								'term' => [
									'interests' => [
										'value' => 'cars',
										'boost' => 2
									]
								]
							],
							[
								'term' => [
									'gender' => [
										'value' => 'male',
										'boost' => 1
									]
								]
							]
						]
					]
				]
			]
		];

		$result = $this->gen->make($this->query_params, $this->search_type_config);

		assertThat($result, equalTo($this->expected_query));
	}

	public function testMakeReturnsExpectedQueryWithPollTypBoostAndFieldValueBoosts()
	{
		$this->query_params = [
			'gender' => 'male',
			'occasion' => 'anniversary'
		];

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'sum',
				'query' => [
	                'function_score' => [
						'query' => [
							'bool' => [
								'should' => [
									[
										'term' => [
											'gender' => [
												'value' => 'male',
												'boost' => 1
											]
										]
									],
									[
										'term' => [
											'occasion' => [
												'value' => 'anniversary',
												'boost' => 3.0
											]
										]
									]
								]
							]
						],
						'functions' => [
							[
								'filter' => [
									'term' => [
										'add_type' => 'detail_view'
									]
								],
								'boost_factor' => 1.2
							]
						]
					]
				]	
			]
		];

		$result = $this->gen->make($this->query_params, $this->search_type_config);

		assertThat($result, equalTo($this->expected_query));
	}

public function arrayRecursiveDiff($aArray1, $aArray2) {
  $aReturn = array();

  foreach ($aArray1 as $mKey => $mValue) {
    if (array_key_exists($mKey, $aArray2)) {
      if (is_array($mValue)) {
        $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
        if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
      } else {
        if ($mValue != $aArray2[$mKey]) {
          $aReturn[$mKey] = $mValue;
        }
      }
    } else {
      $aReturn[$mKey] = $mValue;
    }
  }
  return $aReturn;
} 

	public function testMakeReturnsExpectedQueryWithPollTypeBoostAndFieldSimularities()
	{
		$this->query_params = [
			'gender' => 'male',
			'relation' => 'parent'
		];

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'sum',
				'query' => [
	                'function_score' => [
						'query' => [
							'bool' => [
								'should' => [
									[
										'term' => [
											'gender' => [
												'value' => 'male',
												'boost' => 1
											]
										]
									],
									[
										'term' => [
											'relation' => [
												'value' => 'grandparent',
												'boost' => 1.0
											]
										]
									],
									[
										'term' => [
											'relation' => [
												'value' => 'parent',
												'boost' => 2
											]
										]
									]
								]
							]
						],
						'functions' => [
							[
								'filter' => [
									'term' => [
										'add_type' => 'detail_view'
									]
								],
								'boost_factor' => 1.2
							]
						]
					]
				]	
			]
		];

		$result = $this->gen->make($this->query_params, $this->search_type_config);

		assertThat($result, equalTo($this->expected_query));
	}

	public function testMakeReturnsExpectedQueryWithPollTypeBoostAndFieldSimularitiesAndFieldValueBoost()
	{
		$this->query_params = [
			'gender' => 'male',
			'occasion' => 'birthday'
		];

		$this->expected_query = 
		[
			'has_child' => [
				'type' => 'add',
				'score_type' => 'sum',
				'query' => [
	                'function_score' => [
						'query' => [
							'bool' => [
								'should' => [
									[
										'term' => [
											'gender' => [
												'value' => 'male',
												'boost' => 1
											]
										]
									],
									[
										'term' => [
											'occasion' => [
												'value' => 'anniversary',
												'boost' => 2.0
											]
										]
									],
									[
										'term' => [
											'occasion' => [
												'value' => 'birthday',
												'boost' => 4
											]
										]
									]
								]
							]
						],
						'functions' => [
							[
								'filter' => [
									'term' => [
										'add_type' => 'detail_view'
									]
								],
								'boost_factor' => 1.2
							]
						]
					]
				]	
			]
		];

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
	public function testMakeThrowsInvalidArgWhenConfigForChildScoreTypeIsNotSet()
	{
		unset($this->search_type_config['query']['params']['has_child_score_type']);
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