<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Helper;

trait EloquentIndexSearchTrait {	

	public function indexSearch($search_type, $query, $offset, $limit, array $filters = [], array $fields = [])
	{
		$index_repo = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');

		$index_response = $index_repo->model(self::$index_model)->search($search_type, $query, $offset, $limit, $filters, $fields);
	
		return $this->getIndexResponseModels($index_response);
	}
	
	protected function getIndexResponseModels(array $index_response)
	{		
		$model_collection = $this->whereIn('id', $this->getIdsFromIndexResponse($index_response))->get();

		$model_collection = $this->sortIndexResponseModels($model_collection, $index_response['results']);

		$meta_collection = \Collection::make($index_response['meta']);

		$facets_collection = \Collection::make($index_response['facets']);

		$response_collection = \Collection::make([]);
		$response_collection->put('meta', $meta_collection);
		$response_collection->put('results', $model_collection);
		$response_collection->put('facets', $facets_collection);

		return \Collection::make($response_collection);			
	}

	protected function sortIndexResponseModels($models_collection, $index_results)
	{
		if (empty($index_results))
			return $models_collection;

		foreach ($index_results as $key => $result)
			$sort_legend[$result['_id']] = $key;
		
		return $models_collection->sortBy(function($product) use($sort_legend)
		{
		    return $sort_legend[$product->id];
		});
	}

	protected function getIdsFromIndexResponse(array $index_response)
	{
		foreach ($index_response['results'] as $index_result)
			$ids[] = $index_result['_id'];	

		if (!empty($ids))
			return $ids;

		return false;
	}

}