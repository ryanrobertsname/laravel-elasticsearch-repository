<?php

/**
* Class
*/
class ChildrenCountQueryGeneratorTest extends TestCase
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
					'match_all' => [

					]
				]
			]
		];

		$this->search_type_config = [
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
					]
				],
				'filters' => [

				]
		];

		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add.setup.type')->andReturn('add');

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\ChildrenCountQueryGenerator');
	}
	
	public function testMakeReturnsExpectedQuery()
	{
		$result = $this->gen->make('', $this->search_type_config);

		assertThat($result, identicalTo($this->expected_query));
	}
}