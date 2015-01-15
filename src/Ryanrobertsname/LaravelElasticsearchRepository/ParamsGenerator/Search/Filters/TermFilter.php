<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters;

/**
* Class
*/
class TermFilter implements FilterInterface
{
	protected $config;

	/**
	 * Make filter
	 * @param  array  $params : 'field' 'value'
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
			'term' => [
				$params['field'] => $params['value']
			]
		];	

		return $filter;	
	}

	protected function validateFilterParams(array $params)
	{
		if (
			empty($params['field'])
			||
			!isset($params['value'])
		)
			throw new \InvalidArgumentException('Term filter params invalid.');
	}
}