<?php

/**
* Class
*/
class ESIndexParamsGeneratorTest extends TestCase
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

		$this->expected_index_params = [
			'index' => 'tests',
			'type' => 'test',
			'id' => 100,
			'body' => [
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

		$this->generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator');
	}

	public function testMakeParamsReturnsExpectedParams()
	{
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$item = $this->expected_index_params['body'];
		$item['_id'] = 100;

		$result = $this->generator->makeParams('product', $item);

		assertThat($result, identicalTo($this->expected_index_params));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionIfFieldIsNotInMapping()
	{
		$this->expected_index_params['body']['foobar'] = 'fooed';
		
		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$item = $this->expected_index_params['body'];
		$item['_id'] = 100;

		$result = $this->generator->makeParams('product', $item);
	}

	public function testMakeParamsReturnsExpectedParamsWhenParentIdIsRequired()
	{
		$expected_index_params = [
			'index' => 'tests',
			'type' => 'test',
			'id' => 100,
			'body' => [
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
			],
			'parent' => 1
		];

		$this->config['setup']['mapping']['parent'] = 'product';

		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn($this->config);

		$item = $this->expected_index_params['body'];
		$item['_id'] = 100;
		$item['_parent_id'] = 1;

		$result = $this->generator->makeParams('product_add', $item);

		assertThat($result, identicalTo($expected_index_params));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgExceptionWhenParentIdIsEmptyAndParentIdIsRequired()
	{
		$this->config['setup']['mapping']['parent'] = 'product';

		\Config::shouldReceive('has')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn(true);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product_add')->once()->andReturn($this->config);

		$item = $this->expected_index_params['body'];
		$item['_id'] = 100;

		$this->generator->makeParams('product_add', $item);
	}
	
}