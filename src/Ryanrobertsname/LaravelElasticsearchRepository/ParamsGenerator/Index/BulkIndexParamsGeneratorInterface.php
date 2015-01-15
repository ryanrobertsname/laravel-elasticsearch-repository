<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index;

interface BulkIndexParamsGeneratorInterface
{
	public function makeParams($index_model, array $items);
}