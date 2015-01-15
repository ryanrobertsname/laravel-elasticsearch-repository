<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs;

/**
* Class
*/
class NumberRangeAgg implements AggInterface
{
	protected $field_mapping;

	/**
	 * Make agg
	 * @param  array  $params : 'field' 'interval'
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
			$params['field'].'_histogram' => [
				'histogram' => [
					'field' => $params['field'],
					'interval' => $params['interval'],
				]
			],
			$params['field'].'_stats' => [
				'extended_stats' => [
					'field' => $params['field']
				]
			]
		];		
	}

	protected function validateParams(array $params)
	{
		if (
			empty($params['field'])
			||
			empty($params['interval'])
			||
			!is_integer($params['interval'])
		)
			throw new \InvalidArgumentException('NumberRange agg params invalid.');
	}
}