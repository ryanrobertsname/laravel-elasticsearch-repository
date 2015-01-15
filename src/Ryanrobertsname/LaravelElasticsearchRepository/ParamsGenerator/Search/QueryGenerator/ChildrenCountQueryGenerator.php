<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

/**
* Class
*/
class ChildrenCountQueryGenerator implements QueryGeneratorInterface
{
	use QueryGeneratorBaseTrait;

	protected $config;
	protected $params;

	/**
	 * @param  null $params  	No params for this query generator
	 * @param  array $search_config
	 * @return array
	 */
	public function make($params = null, array $search_config)
	{
		if (
			empty($search_config['query']['params']['has_child_index_model']) 
			|| empty($search_config['query']['params']['has_child_score_type'])
		)
			throw new \InvalidArgumentException();

		$this->params = $params;
		$this->config = $search_config;

		return $this->makeBaseQuery();

	}

	protected function makeBaseQuery()
	{
		return 
		[
			'has_child' => [
				'type' => $this->getIndexModelType($this->config['query']['params']['has_child_index_model']),
				'score_type' => $this->config['query']['params']['has_child_score_type'],
				'query' => [
					'match_all' => [

					]
				]
			]
		];
	}
}