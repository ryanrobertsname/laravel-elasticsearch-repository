<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index;

interface IndexParamsGeneratorInterface
{
	public function makeParams($index_model, array $item);
}