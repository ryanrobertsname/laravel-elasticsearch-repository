<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

/**
* Class
*/
class FilterByChildrenQueryGenerator implements QueryGeneratorInterface
{
	use QueryGeneratorBaseTrait;

	protected $config;
	protected $params;

	/**
	 * @param  array $params  Child index_model field and values to match for filtering      
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
		)
			throw new \InvalidArgumentException();

		$this->config = $search_config;
		$this->bool_match_type = 'should';

		return $this->addFilterConditions($this->makeBaseQuery(), $params);
	}

	protected function makeBaseQuery()
	{
		return 
		[
			'has_child' => [
				'type' => $this->getIndexModelType($this->config['query']['params']['has_child_index_model']),
				'score_type' => 'none',
				'query' => [

				]
			]
		];
	}

	protected function addFilterConditions(array $query, array $params)
	{	
		foreach ($params as $filter_type => $field_filters):

			$query = $this->addFieldFilters($query, $filter_type, $field_filters);
		
		endforeach;

		return $query;
	}

	protected function addFieldFilters(array $query, $filter_type, array $field_filters)
	{
		$this->validateFilterType($filter_type);

		foreach ($field_filters as $field => $value_s)
			if (!is_array($value_s)):
				$query['has_child']['query']['bool'][$filter_type][] = $this->makeSingleValueFilter($field, $value_s);
			else:
				$query['has_child']['query']['bool'][$filter_type][] = $this->makeMultiValueFilter($field, $value_s);
			endif;

		return $query;
	}

	protected function makeSingleValueFilter($field, $value)
	{
		if (!array_key_exists($field, $this->config['query']['params']['fields']))
			throw new \InvalidArgumentException($field.' field not in search params config.');

		return 
		[
			'term' => [
				$field => [
					'value' => $value
				]
			]
		];
	}

	protected function makeMultiValueFilter($field, $values)
	{
		foreach ($values as $filter_type => $value_s)
			if (!is_array($value_s)):
				$filter['bool'][$filter_type][] = $this->makeSingleValueFilter($field, $value_s);
			else:
				foreach ($value_s as $value)
					$filter['bool'][$filter_type][] = $this->makeSingleValueFilter($field, $value);
			endif;

		return $filter;
	}

	protected function validateFilterType($filter_type)
	{
		if (!in_array($filter_type, ['must', 'should', 'must_not']))
			throw new \InvalidArgumentException('Filter type '.$filter_type.' invalid.');
	}
	
}