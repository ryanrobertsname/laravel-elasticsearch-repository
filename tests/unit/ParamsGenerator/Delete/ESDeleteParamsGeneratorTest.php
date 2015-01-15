<?php

/**
* Class
*/
class ESDeleteParamsGeneratorTest extends TestCase
{
	protected $gen;

	public function setUp()
	{
		parent::setUp();

		$this->config = [
			'setup' => [
				//index name
				'index' => 'tests',

				//type name
				'type' => 'test'
			]
		];

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator');
	}

	public function testMakeParamsReturnsExpectedParams()
	{
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$doc_id = 100;

		$params_mock = [
			'index' => 'tests',
			'type' => 'test',
			'id' => $doc_id
		];

		$result = $this->gen->makeParams('product', $doc_id);

		assertThat($result, identicalTo($params_mock));
	}

	public function testMakeParamsReturnsExpectedParamsWhenRefreshIsRequested()
	{
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);

		$doc_id = 100;

		$params_mock = [
			'index' => 'tests',
			'type' => 'test',
			'id' => $doc_id,
			'refresh' => true
		];

		$result = $this->gen->makeParams('product', $doc_id, true);

		assertThat($result, identicalTo($params_mock));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgumentExceptionIfDocIdConfigCannotBeRetrieved()
	{
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn('');

		$this->gen->makeParams('product', 1);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgumentExceptionIfDocIdArgIsEmpty()
	{
		$this->gen->makeParams('product', '');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testDeleteThrowsInvalidArgumentExceptionIfDocIdArgIsNotAnInteger()
	{
		$this->gen->makeParams('product', 'one');
	}	
}