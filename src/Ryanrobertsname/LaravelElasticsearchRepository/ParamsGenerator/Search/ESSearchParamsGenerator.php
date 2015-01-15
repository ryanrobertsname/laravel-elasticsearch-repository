<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search;

use Illuminate\Support\MessageBag;

/**
* Class
*/
class ESSearchParamsGenerator implements SearchParamsGeneratorInterface
{
	protected $config;
	protected $errors;
	protected $index;
	protected $index_type;
	protected $search_type;

	public function __construct(MessageBag $message_bag)
	{
		$this->errors = $message_bag;
	}

	protected function getConfig($index_model, $search_type)
	{
		$config_var = 'laravel-elasticsearch-repository::index.index_models.'.$index_model;
		
		if (!\Config::has($config_var))
			throw new \InvalidArgumentException('Index model cannot be found in config');

		$this->config = \Config::get($config_var);

		if (empty($this->config['search'][$search_type]))
			throw new \InvalidArgumentException('Config for '.$search_type.' search type does not exist');

		$this->index = $this->config['setup']['index'];
		$this->index_type = $this->config['setup']['type'];
		$this->search_type = $search_type;
	}

	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Make search params
	 * @param  string  $index_model
	 * @param  mixed  $query_params
	 * @param  integer  $offset
	 * @param  integer  $limit
	 * @param  array  $source_fields
	 * @param  array $filters
	 * IE: 
	 * [
	 *     'price' => [
	 *     		'filter' => 'NumberRange',
	 *     		'params' => [
	 *     	   		'field' => 'min_price',
	 * 		        'min' => 0,
	 * 		        'max' => 100
	 * 		     ]
	 * 	    ]
	 * ]
	 * @return array          
	 */
	public function makeParams($index_model, $search_type, $query_params, $offset, $limit, array $source_fields = [], array $filters = [])
	{
		$this->getConfig($index_model, $search_type);

		if (!is_integer($limit) || !is_integer($offset)) throw new \InvalidArgumentException('Limit and offset args must be integers');

		$this->validateFields($source_fields);

		$search_params = $this->makeParamBase($offset, $limit);

		$search_params = $this->addQuery($search_params, $search_type, $query_params);

		$search_params = $this->addSourceFieldParams($search_params, $source_fields);

		$search_params = $this->addAggParams($search_params);

		$search_params = $this->addFilterParams($search_params, $filters);

		return $search_params;
	}

	protected function makeParamBase($offset, $limit)
	{
		return[
			'index' => $this->index,
			'type' => $this->index_type,
			'body' => [
				'from' => $offset,
				'size' => $limit,
				'_source' => false
			]
		];
	}

	protected function addQuery(array $search_params, $search_type, $query_params)
	{
		//if query generator is null per config, return without query
		if (is_null($this->config['search'][$search_type]['query']['type']))
			return $search_params;
		
		$generator = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator\\'.ucwords(camel_case($this->config['search'][$search_type]['query']['type'])).'QueryGenerator');
		
		$search_params['body']['query']['filtered']['query'] = $generator->make($query_params, $this->config['search'][$this->search_type]);

		return $search_params;
	}
	
	protected function addSourceFieldParams(array $params, array $source_fields)
	{
		if (empty($source_fields)) return $params;

		foreach ($source_fields as $field):
			$this->validateField($field);

			$params['body']['_source'][] = $field;
		endforeach;

		return $params;
	}

	protected function validateFields(array $fields)
	{
		foreach ($fields as $field)
			$this->validateField($field);
	}

	protected function validateField($field)
	{
		if (!isset($this->config['setup']['mapping']['properties'][$field]))
			throw new \InvalidArgumentException($field.' is an invalid field');		
	}

	protected function addAggParams(array $params)
	{
		$search_aggs = $this->config['search'][$this->search_type]['aggs'];

		if (empty($search_aggs)) return $params;

		$params['body']['aggs'] = [];

		foreach ($search_aggs as $user_agg_name  =>  $agg_data)
			$params['body']['aggs'] = array_merge($params['body']['aggs'], $this->getAgg($agg_data['agg'], $agg_data['params']));

		return $params;
	}

	protected function getAgg($agg_name, array $agg_params = [])
	{
		$agg = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs\\'.$agg_name);

		$this->validateField($agg_params['field']);

		return $agg->make($agg_params);	
	}

	protected function addFilterParams(array $params, array $filters)
	{
		//use default filters from config, and makeParams specified filters
		$filters = array_merge($filters, $this->config['search'][$this->search_type]['filters']);
		
		if (empty($filters)) return $params;

		$filter_key = 0;
		$last_filter_set_key = 0;
		$counter = 0;
		foreach ($filters as $user_filter_name => $filter_data):

			//fyi $logged_filters are only for those that can be combined with other filter instances
			
			if ($counter != 0):
				if (isset($filter_data['combine_with_like_instances']) && $filter_data['combine_with_like_instances'] === false):
					$filter_key = $last_filter_set_key + 1;
					$last_filter_set_key = $filter_key;
				elseif (!isset($logged_filters[$filter_data['filter']])):
					$filter_key = $last_filter_set_key + 1;
					$last_filter_set_key = $filter_key;
					$logged_filters[$filter_data['filter']] = $filter_key;
				else:
					$filter_key = $logged_filters[$filter_data['filter']];
				endif;
			elseif (!isset($filter_data['combine_with_like_instances']) || $filter_data['combine_with_like_instances'] === true):
				$logged_filters[$filter_data['filter']] = $filter_key;
			endif;

			$params['body']['query']['filtered']['filter']['bool']['must'][$filter_key]['or'][] = $this->getFilter($filter_data['filter'], $filter_data['params']);
			$counter += 1;

		endforeach;

		return $params;
	}

	protected function getFilter($filter_name, array $filter_params = [])
	{
		$filter = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters\\'.$filter_name);

		$this->validateField($filter_params['field']);

		return $filter->make($filter_params);
	}
}