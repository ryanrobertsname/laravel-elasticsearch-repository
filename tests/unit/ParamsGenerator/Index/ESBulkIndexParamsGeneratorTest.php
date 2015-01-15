<?php

/**
* Class
*/
class ESBulkIndexParamsGeneratorTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->config = [
			'setup' => [
				//index name
				'index' => 'tests',

				//type name
				'type' => 'test',

				//for mapping
				'mapping' => [
					'properties' => [
						'soft_delete_status' => 'integer',
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
					],
					'parent' => null
				]
			]
		];

		$this->items_stub = [
			[
				'_id' => 100,
				'datetime' => '2014-02-14',
				'title' => 'test title',
				'descriptions' => ['test desc1', 'test desc2'],
				'features' => ['test feat1', 'test feat2'],
				'binding' => 'test binding',
				'brand' => 'test brand',
				'manufacturer' => 'test manuf',
				'model' => 'test model',
				'group' => 'test group',
				'size' => 'test size',
				'clothing_size' => 'test clothing size',
				'occasions' => ['test occ1', 'test occ2'],
				'min_price' => 1,
				'max_price' => 10,
				'soft_delete_status' => 0
			]
		];

		$this->generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESBulkIndexParamsGenerator');
	}

	public function testMakeParamsReturnsExpectedParams()
	{
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$result = $this->generator->makeParams('product', $this->items_stub);

		$expected_response['index'] = 'tests';
		$expected_response['type'] = 'test';
		$id = $this->items_stub[0]['_id'];
		unset($this->items_stub[0]['_id']);
		$expected_response['body'] = 
			json_encode(['index' => ['_id' => $id]])."\n".json_encode($this->items_stub[0])."\n";

		assertThat($result, identicalTo($expected_response));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionIfFieldIsNotInMapping()
	{		
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$this->items_stub[0]['foobar'] = 'test';

		$this->generator->makeParams('product', $this->items_stub);
	}

	public function testMakeParamsReturnsExpectedParamsWhenParentIdIsRequired()
	{
		$this->config['setup']['mapping']['parent'] = 'product';

		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn($this->config);

		$this->items_stub[0]['_parent_id'] = 200;

		$response = $this->generator->makeParams('product_add', $this->items_stub);

		$expected_response['index'] = 'tests';
		$expected_response['type'] = 'test';
		$id = $this->items_stub[0]['_id'];
		unset($this->items_stub[0]['_id']);
		unset($this->items_stub[0]['_parent_id']);
		$expected_response['body'] = 
			json_encode(['index' => ['_id' => $id, '_parent' => 200]])."\n".json_encode($this->items_stub[0])."\n";

		assertThat($response, identicalTo($expected_response));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionWhenParentIdIsEmptyAndParentIdIsRequired()
	{
		$this->config['setup']['mapping']['parent'] = 'product';

		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn($this->config);

		$this->generator->makeParams('product_add', $this->items_stub);
	}
	
}