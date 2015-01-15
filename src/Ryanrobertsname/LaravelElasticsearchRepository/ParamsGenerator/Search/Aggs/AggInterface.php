<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\Aggs;

interface AggInterface
{
	public function make(array $params);
}