<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

/**
* Generator for basic query string query
*/
class StringQueryGenerator implements QueryGeneratorInterface
{
	protected $config;
	protected $query_string;

	/**
	 * @param  array  $params        Keyword string to match
	 * @param  array  $search_config
	 * @return array
	 */
	public function make($params, array $search_config)
	{
		if (empty($params) || !is_string($params) || empty($search_config['query']['params']['fields']) || !isset($search_config['query']['params']['append']))
			throw new \InvalidArgumentException();

		if (strlen($params) > 100)
			throw new \InvalidArgumentException('Keyword query string length exceeds 100 characters.');

		$this->config = $search_config;
		$this->query_string = $this->stripNonAlphaNumerics($params);

		$query = $this->makeBaseQuery();
		return $this->addSearchFields($query);
	}

	protected function stripNonAlphaNumerics($string)
	{
		return trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $string));
	}
	
	protected function makeBaseQuery()
	{
		return [
			'query_string' => [
				'query' => $this->query_string.$this->config['query']['params']['append']
			]
		];
	}

	protected function addSearchFields(array $query)
	{
		foreach ($this->config['query']['params']['fields'] as $field => $weight)
			$query['query_string']['fields'][] = $field.$weight;

		return $query;
	}
	
	
}