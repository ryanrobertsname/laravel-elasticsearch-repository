<?php

/**
* Class
*/
class StringQueryGeneratorTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->expected_query = 
		[
			'query_string' => [
				'query' => 'test query~1',
				'fields' => [
					'title^1', 'descriptions^2', 'features^3', 'binding^4', 'brand^5', 'manufacturer^6', 'model^7', 'group^8', 'size^9',
					'clothing_size^10', 'occasions^11'
				]
			]
		];

		$this->query_params = 'test, query';

		$this->search_type_config = [
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
				'NumberRangeAgg' => [
					'field' => 'min_price'
				]
			],
			'filters' => [

			]
		];

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\StringQueryGenerator');
	}
	
	public function testMakeReturnsExpectedQuery()
	{
		$result = $this->gen->make('test query', $this->search_type_config);

		assertThat($result, identicalTo($this->expected_query));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgWhenQueryStingIsLongerThan100Chars()
	{
		$query = str_pad('a', 101);

		$this->gen->make($query, $this->search_type_config);
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
	public function testMakeThrowsInvalidArgWhenConfigForQueryAppendIsNotSet()
	{
		unset($this->search_type_config['query']['params']['append']);
		$this->gen->make('test query', $this->search_type_config);
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgWhenConfigForFieldsIsEmpty()
	{
		$this->search_type_config['query']['params']['fields'] = [];
		$this->gen->make('test query', $this->search_type_config);
	}
}