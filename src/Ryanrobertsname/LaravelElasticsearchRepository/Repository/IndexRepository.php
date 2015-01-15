<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Repository;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator as NumberRangeFacetsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator as TermFacetsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESIndexParamsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index\ESBulkIndexParamsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\ESSearchParamsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteParamsGenerator;
use Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete\ESDeleteWithChildrenParamsGenerator;

/**
* Class
*/
class IndexRepository implements IndexRepositoryInterface
{
	use BaseTrait;

	protected $index_model;
	protected $index;
	protected $type;
	protected $config;
	protected $number_range_facets_generator;
	protected $term_facets_generator;
	protected $es_client;
	protected $search_params_generator;
	protected $index_params_generator;
	protected $bulk_index_params_generator;
	protected $delete_params_generator;
	protected $delete_with_children_params_generator;
	
	public function __construct(TermFacetsGenerator $term_facets_generator, NumberRangeFacetsGenerator $number_range_facets_generator, ESIndexParamsGenerator $index_params_generator, ESBulkIndexParamsGenerator $bulk_index_params_generator, ESSearchParamsGenerator $search_params_generator, ESDeleteParamsGenerator $delete_params_generator, ESDeleteWithChildrenParamsGenerator $delete_with_children_params_generator)
	{
		$this->index_params_generator = $index_params_generator;
		$this->bulk_index_params_generator = $bulk_index_params_generator;
		$this->search_params_generator = $search_params_generator;
		$this->delete_params_generator = $delete_params_generator;
		$this->delete_with_children_params_generator = $delete_with_children_params_generator;
		$this->number_range_facets_generator = $number_range_facets_generator;
		$this->term_facets_generator = $term_facets_generator;
		$this->es_client = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
	}

	/**
	 * Assigned the index model from the index config file
	 * @param  string $index_model
	 * @return $this
	 */
	public function model($index_model)
	{
		$this->config = \Config::get('laravel-elasticsearch-repository::index.index_models.'.$index_model); //get main config
	
		if (empty($this->config))
			throw new \InvalidArgumentException('Invalid index or type, not able to find matching index model in index config file.');
	
		$this->index_model = $index_model;
		$this->index = $this->config['setup']['index'];
		$this->type = $this->config['setup']['type'];

		return $this;
	}

	/**
	 * Find products most like this id
	 * @param  integer $id 
	 * @param  integer $limit
	 * @param  array   $custom_params Custom first level params for es client, view elasticsearch client.php file for options
	 * @return array
	 */
	public function mltSearch($id, $limit, $custom_params = [])
	{
		//search type reference for config
		$search_type = 'mlt_search';

		if (!is_integer($limit))
			throw new \InvalidArgumentException();

		//not currently using a query params generator due to the simple nature of this
		//mlt query does not seem to be working currently, when it does we can create an mlt query generator
		//and let the search params generator handle this
		$params = [
			'index' => $this->config['setup']['index'],
			'type' => $this->config['setup']['type'],
			'id' => $id,
			'search_size' => $limit,
			'body' => [
				'_source' => false,
				'filter' => [
					'bool' => [
						'must' => [
							[
								'or' => [
									[
										'term' => [
											'soft_delete_status' => 0
										]
									]
								]
							]
						]
					]
				]
			]
		];

		//set params min term freq if in config
		if (!empty($this->config['search']['mlt_search']['query']['params']['min_term_freq']))
			$params['min_term_freq'] = $this->config['search']['mlt_search']['query']['params']['min_term_freq'];

		//set set params min doc freq if in config
		if (!empty($this->config['search']['mlt_search']['query']['params']['min_doc_freq']))
			$params['min_doc_freq'] = $this->config['search']['mlt_search']['query']['params']['min_doc_freq'];

		//merge in custom client params if needed
		if (!empty($custom_params))
			$params = array_merge($params, $custom_params);

		$response = $this->clientMlt($params);

		return $this->makeSearchResponse($search_type, $response);
	}

	protected function clientMlt($mlt_params)
	{
		return $this->es_client->mlt($mlt_params);		
	}

	public function search($search_type, $query, $offset, $limit, array $filters = [], array $fields = [])
	{
		$this->validateNumberOfFilters($filters);

		$search_params = $this->search_params_generator->makeParams($this->index_model, $search_type, $query, $offset, $limit, $fields, $filters);

		if (!$search_params):
			$this->errors->merge($this->search_params_generator->errors()->all());
			return false;
		endif;

		$response = $this->clientSearch($search_params);

		return $this->makeSearchResponse($search_type, $response);
	}

	protected function clientSearch($search_params)
	{
		return $this->es_client->search($search_params);
	}

	protected function makeSearchResponse($search_type, array $response)
	{	
		$new_response['meta'] = [
			'total_hits' => $response['hits']['total']
		];
		$new_response['results'] = $response['hits']['hits'];
		$new_response['facets'] = [];


		//if search has aggregation results, process them
		if (!empty($response['aggregations']) && !empty($this->config['search'][$search_type]['aggs'])):

			foreach ($this->config['search'][$search_type]['aggs'] as $agg_key => $agg_params):

				if ($agg_params['agg'] == 'NumberRangeAgg')
				{
					if (!empty($response['aggregations'][$agg_params['params']['field'].'_stats']) && !empty($response['aggregations'][$agg_params['params']['field'].'_histogram']['buckets']))
						$new_response['facets']['price'] = 
							$this->number_range_facets_generator->make(
								ucwords($agg_key),
								$agg_params['params']['field'],
								$agg_params['params']['max_aggs'], 
								$agg_params['params']['interval'], 
								$response['aggregations'][$agg_params['params']['field'].'_stats'], 
								$response['aggregations'][$agg_params['params']['field'].'_histogram']['buckets']
							);
				}
				elseif ($agg_params['agg'] == 'TermAgg')
				{
					if (!empty($response['aggregations'][$agg_key]['buckets']))
					$new_response['facets'][$agg_key] = 
						$this->term_facets_generator->make(
							$agg_params['params']['field'], 
							$response['aggregations'][$agg_key]['buckets']
						);
				}

			endforeach;
		
		endif;

		return $new_response;
	}

	protected function validateNumberOfFilters(array $filters)
	{
		if (count($filters) > 200)
			throw new \InvalidArgumentException('Filter count is greater than 200');
	}

	public function deleteSelfAndChildren($self_id, array $children_index_models)
	{		
		$params = $this->delete_with_children_params_generator->makeParams($this->index_model, $children_index_models, $self_id);

		$response = $this->clientDeleteByQuery($params);

		if ($this->logOpErrors($response, 'delete_with_children_operation')) throw new \RunTimeException();

		//check for failures
		if (empty($response['_indices']))
			throw new \RunTimeException('Failure trying to delete documents');		
		foreach ($response['_indices'] as $index_response)
			if ($index_response['_shards']['failed'] > 0)
				throw new \RunTimeException('Failure trying to delete documents');
	}

}