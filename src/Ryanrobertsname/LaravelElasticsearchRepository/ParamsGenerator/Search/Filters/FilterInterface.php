<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Filters;

interface FilterInterface
{
	public function make(array $params);
}

