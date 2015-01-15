<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

/**
* Class
*/
class PollQueryGenerator implements QueryGeneratorInterface
{
	use QueryGeneratorBaseTrait;

	protected $config;
	protected $params;

	/**
	 * @param  array $params  Child index_model field and values to match for polling      
	 * @param  array $search_config
	 * @return array
	 */
	public function make($params, array $search_config)
	{
		if (
			empty($params) 
			|| !is_array($params) 
			|| empty($search_config['query']['params']['fields'])
			|| empty($search_config['query']['params']['has_child_index_model']) 
			|| empty($search_config['query']['params']['has_child_score_type'])
		)
			throw new \InvalidArgumentException();

		$this->params = $params;
		$this->config = $search_config;
		$this->bool_match_type = 'should';

		return $this->addPollTypeBoosts($this->addPollConditions($this->makeBaseQuery()));
	}

	protected function makeBaseQuery()
	{
		return 
		[
			'has_child' => [
				'type' => $this->getIndexModelType($this->config['query']['params']['has_child_index_model']),
				'score_type' => $this->config['query']['params']['has_child_score_type'],
				'query' => [
					'bool' => [
						$this->bool_match_type => [
							
						]
					]
				]
			]
		];
	}

	protected function addPollConditions(array $query)
	{	
		foreach ($this->params as $field => $value):

			//if value is an array, make each value into a separate poll condition
			if (is_array($value)):
				foreach ($value as $val)
					$query = $this->addPollConditionsForFieldValue($query, $field, $val);

				continue;
			endif;

			//if value is not an array
			$query = $this->addPollConditionsForFieldValue($query, $field, $value);
		
		endforeach;

		return $query;
	}

	protected function addPollConditionsForFieldValue(array $query, $field, $value)
	{
		//provide field level boost and adjust for field value
		$boost = $this->adjustBoostForFieldValue($this->config['query']['params']['fields'][$field], $field, $value);

		//add similar field values to query
		$query = $this->addPollConditionsForSimilarFields($query, $boost, $field, $value);

		//add provided field value combo to query
		$query['has_child']['query']['bool'][$this->bool_match_type][] = $this->makePollCondition($field, $value, $boost);

		return $query;
	}

	protected function addPollConditionsForSimilarFields(array $query, $boost, $field, $value)
	{
		if (!isset($this->config['query']['params']['field_simularity_matches'][$field][$value]))
			return $query;

		foreach ($this->config['query']['params']['field_simularity_matches'][$field][$value] as $sim_value => $sim_value_boost)
			$query['has_child']['query']['bool'][$this->bool_match_type][] = $this->makePollCondition($field, $sim_value, ($sim_value_boost * $boost));

		return $query;
	}

	protected function adjustBoostForFieldValue($boost, $field, $value)
	{
		if (!isset($this->config['query']['params']['field_value_boosts'][$field][$value]))
			return $boost;

		$boost *= $this->config['query']['params']['field_value_boosts'][$field][$value];

		return $boost;
	}

	protected function makePollCondition($field, $value, $boost)
	{
		return 
		[
			'term' => [
				$field => [
					'value' => $value,
					'boost' => $boost
				]
			]
		];
	}

	protected function addPollTypeBoosts(array $query)
	{		
		if (empty($this->config['query']['params']['poll_type_boosts']))
			return $query;

		$query = $this->addPollTypeBoostBase($query);

		return $this->addPollTypeBoostFilters($query);
	}

	protected function addPollTypeBoostBase(array $query)
	{
		$query['has_child']['query'] = ['function_score' => ['query' => $query['has_child']['query']]];
		$query['has_child']['query']['function_score']['functions'] = [];

		return $query;
	}

	protected function addPollTypeBoostFilters(array $query)
	{
		foreach ($this->config['query']['params']['poll_type_boosts'] as $field => $values)
			foreach ($values as $value => $boost)
				$query['has_child']['query']['function_score']['functions'][] = $this->makePollTypeBoostFilter($field, $value, $boost);
	
		return $query;
	}

	protected function makePollTypeBoostFilter($field, $value, $boost)
	{
		return [
			'filter' => [
				'term' => [
					$field => $value
				]
			],
			'boost_factor' => $boost
		];
	}
}