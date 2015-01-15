<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete;

interface ESDeleteParamsGeneratorInterface
{
	public function makeParams($index_model, $id, $refresh = false);

	public function errors();
}