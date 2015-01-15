<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs;

/**
* Class
*/
class TermAgg implements AggInterface
{
	protected $field_mapping;

	/**
	 * Make agg
	 * @param  array  $params : 'field'
	 * @return array         
	 */
	public function make(array $params)
	{
		$this->validateParams($params);

		return $this->makeAgg($params);
	}
	
	protected function makeAgg(array $params)
	{
		return [
			$params['field'] => [
				'terms' => [
					'field' => $params['field']
				]
			]
		];		
	}

	protected function validateParams(array $params)
	{
		if (
			empty($params['field'])
		)
			throw new \InvalidArgumentException('Term agg params invalid.');
	}
}