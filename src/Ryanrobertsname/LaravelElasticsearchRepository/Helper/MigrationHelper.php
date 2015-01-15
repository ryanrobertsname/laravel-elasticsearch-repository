<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Helper;

/**
* Migration Helper
*/
class MigrationHelper
{
	protected $client;

	function __construct()
	{
		$this->client = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient');
	}

	public function createIndex($index)
	{
		$this->client->indices()->create(['index' => $index]);
	}
	
	public function deleteIndex($index)
	{
		$this->client->indices()->delete(['index' => $index]);
	}
	
	public function createIndexColumns($index, $type, array $columns, $parent_type = null)
	{
		$params['index'] = $index;
		$params['type']  = $type;

		foreach ($columns as $column => $map_type)
			if (is_array($map_type)):
				$mapping_fields[$column] = $map_type;
			else:
				$mapping_fields[$column] = ['type' => $map_type];
			endif;

		$type_mappings = array(
		    'properties' => $mapping_fields
		);

		if (!is_null($parent_type))
			$type_mappings['_parent']['type'] = $parent_type;

		$params['body'][$type] = $type_mappings;

		$this->client->indices()->putMapping($params);
	}

	public function deleteIndexColumns($index, $type)
	{
		$this->client->indices()->deleteMapping(['index' => $index, 'type' => $type]);
	}
	
	
}