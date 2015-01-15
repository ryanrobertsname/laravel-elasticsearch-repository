<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters;

/**
* Class
*/
class NumberRangeFilter implements FilterInterface
{
	protected $config;

	/**
	 * Make filter
	 * @param  array  $params : 'min', and / or 'max'
	 * @return array         
	 */
	public function make(array $params)
	{
		$this->validateFilterParams($params);

		return $this->makeFilter($params);
	}
	
	protected function makeFilter(array $params)
	{
		$filter = [
			'range' => [
				$params['field'] => []
			]
		];	

		if (isset($params['min']))
			$filter['range'][$params['field']]['gte'] = $params['min'];

		if (isset($params['max']))
			$filter['range'][$params['field']]['lte'] = $params['max'];	

		return $filter;	
	}

	protected function validateFilterParams(array $params)
	{
		if (
			empty($params['field'])
			||
			(!isset($params['min']) && !isset($params['max']))
			|| 
			(isset($params['min']) && !is_integer($params['min']))
			|| 
			(isset($params['max']) && !is_integer($params['max']))
		)
			throw new \InvalidArgumentException('Number filter params invalid.');
	}
}