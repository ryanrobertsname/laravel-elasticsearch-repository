<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search;

interface SearchParamsGeneratorInterface
{
	public function makeParams($index_model, $search_type, $query, $offset, $limit, array $source_fields = [], array $filters = []);

	public function errors();
}