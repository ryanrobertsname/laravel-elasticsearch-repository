<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Search\QueryGenerator;

trait QueryGeneratorBaseTrait {

	protected function getIndexModelType($index_model)
	{		
		return \Config::get('laravel-elasticsearch-repository::index.index_models.'.$index_model.'.setup.type');
	}

}