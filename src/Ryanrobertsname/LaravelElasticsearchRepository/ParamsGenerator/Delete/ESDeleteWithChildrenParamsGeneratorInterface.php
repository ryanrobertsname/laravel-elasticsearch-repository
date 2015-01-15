<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete;

interface ESDeleteWithChildrenParamsGeneratorInterface
{
	public function makeParams($index_model, array $children_types, $id);

	public function errors();
}