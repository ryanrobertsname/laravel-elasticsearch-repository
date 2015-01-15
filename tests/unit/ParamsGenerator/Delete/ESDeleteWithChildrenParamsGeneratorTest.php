<?php

/**
* Class
*/
class ESDeleteWithChildrenParamsGeneratorTest extends TestCase
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

		$this->child_config = [
			'setup' => [
				//index name
				'index' => 'tests',

				//type name
				'type' => 'testchild'
			]
		];

		$this->gen = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator');
	}

	public function testMakeParamsReturnsExpectedParams()
	{
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn($this->config);
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.add')->once()->andReturn($this->child_config);

		$doc_id = 100;

		$params_mock = [
			'index' => 'tests',
			'type' => 'test,testchild',
			'body' => [
				'query' => [
					'bool' => [
						'should' => [
							[
								'term' => [
									'_parent' => 'test#'.$doc_id
								]
							],
							[
								'ids' => [
									'type' => 'test',
									'values' => [$doc_id]
								]
							]
						]
					]
				]
			]
		];

		$result = $this->gen->makeParams('product', ['add'], $doc_id);

		assertThat($result, identicalTo($params_mock));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgumentExceptionIfDocIdConfigCannotBeRetrieved()
	{
		\Config::shouldReceive('get')->with('laravel-elasticsearch-repository::index.index_models.product')->once()->andReturn('');

		$this->gen->makeParams('product', ['childtype'], 1);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeParamsThrowsInvalidArgumentExceptionIfDocIdArgIsEmpty()
	{
		$this->gen->makeParams('product', ['childtype'], '');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testDeleteThrowsInvalidArgumentExceptionIfDocIdArgIsNotAnInteger()
	{
		$this->gen->makeParams('product', ['childtype'], 'one');
	}	
}