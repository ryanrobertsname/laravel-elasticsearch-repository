<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Helper;

trait EloquentAutoIndexTrait {

	public function delete()
	{
		if (empty(self::$index_model))
			return parent::delete();

		$model_config = \Config::get('laravel-elasticsearch-repository::index.index_models.'.self::$index_model);

		if (empty($model_config))
			throw new \InvalidArgumentException('Index model '.self::$index_model.' not found or empty in index config file.');

		\DB::beginTransaction();

		try {

			$response = parent::delete();

			if (!$response)
				return $response;

			$this->deleteFromIndex($model_config);

		} catch (\Exception $e) {

			\DB::rollback();
			throw $e;

		}

		\DB::commit();

		return $response;
	}

	private function deleteFromIndex($model_config)
	{
		$index_repo = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');
		
		$index_repo->model(self::$index_model)->delete($this->id);
	}
	
	public function save(array $options = array())
	{
		if (empty(self::$index_model))
			return parent::save();

		$model_config = \Config::get('laravel-elasticsearch-repository::index.index_models.'.self::$index_model);

		if (empty($model_config))
			throw new \InvalidArgumentException('Index model '.self::$index_model.' not found or empty in index config file.');

		\DB::beginTransaction();

		try {

			$response = parent::save();

			if (!$response)
				return $response;

			$this->saveToIndex($model_config);

		} catch (\Exception $e) {

			\DB::rollback();
			throw $e;

		}

		\DB::commit();

		return $response;
	}

	private function saveToIndex(array $model_config)
	{		
		if (empty($model_config['setup']['mapping']['properties']))
			throw new \InvalidArgumentException('Index model '.self::$index_model.' mapping properties found or empty in index config file.');

		$properties_config = $model_config['setup']['mapping']['properties'];

		//go through index properties / columns in config and capture values from model for indexing
		foreach ($properties_config as $column => $column_mapping):
			
			//if the column is not set on the model but there is a mutator,
			//then use that to get value for indexing
			if (!isset($this->$column) && method_exists($this, 'set'.studly_case($column).'Attribute')):
				$columns[$column] = $this->{'set'.studly_case($column).'Attribute'}(null);
				continue;
			endif;

			//if the column is not set on the model and there is no mutator throw exception
			if (!isset($this->$column))
				throw new \InvalidArgumentException('Field '.$column.' does not exist for indexoing on this eloquent object.');
			
			//lastly, use column value that exists on model
			$columns[$column] = $this->$column;

		endforeach;

		if (!isset($this->id)):
			var_dump($this);
			throw new \InvalidArgumentException('Eloquent id field missing for indexing.');
		endif;

		$columns['_id'] = $this->id;

		$index_repo = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');
		
		$index_repo->model(self::$index_model)->index($columns);
	}

	public function mutateIndexColumnValue($column)
	{
		if (!isset(self::$auto_index_column_mutators[$column]) || !is_callable(self::$auto_index_column_mutators[$column]))
			throw new \InvalidArgumentException('Missing index column mutator function for column '.$column);

		return self::$auto_index_column_mutators[$column]();
	}
	
	
	
}