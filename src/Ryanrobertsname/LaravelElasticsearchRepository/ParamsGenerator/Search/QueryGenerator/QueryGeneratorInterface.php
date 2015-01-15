<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

/**
 * Generates the specific query parameters for the search param generator, implementation in dependent
 * on the query type that is in the config file and associated to the index / type / search type that is being handled
 */
interface QueryGeneratorInterface
{
	public function make($params, array $search_config);
}