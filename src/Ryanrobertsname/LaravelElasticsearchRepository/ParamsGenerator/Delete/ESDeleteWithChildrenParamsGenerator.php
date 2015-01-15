<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete;

use Illuminate\Support\MessageBag;

/**
* Class
*/
class ESDeleteWithChildrenParamsGenerator implements ESDeleteWithChildrenParamsGeneratorInterface
{
	protected $errors;
	protected $config;
	protected $index;
	protected $index_type;

	public function __construct(MessageBag $message_bag)
	{
		$this->errors = $message_bag;
	}

	protected function setConfig($index_model)
	{
		$this->config = $this->getConfig($index_model);

		$this->index = $this->config['setup']['index'];
		$this->index_type = $this->config['setup']['type'];
	}

	protected function getConfig($index_model)
	{
		$config_var = 'laravel-elasticsearch-repository::index.index_models.'.$index_model;
		$config = \Config::get($config_var);

		if (empty($config))
			throw new \InvalidArgumentException('Index model cannot be found in config file.');

		return $config;
	}

	protected function makeTypeString($index, array $children_index_models)
	{
		$types_string = $this->index_type;

		foreach ($children_index_models as $child_index_models)
		{
			$config = $this->getConfig($child_index_models);
			$types_string .= ','.$config['setup']['type'];
		}

		return $types_string;
	}

	public function makeParams($index_model, array $children_index_models, $id)
	{
		//refresh will automatically take place with this delete by query

		if (empty($id) || !is_integer($id) || empty($children_index_models))
			throw new \InvalidArgumentException();

		$this->setConfig($index_model);

		$params = [
			'index' => $this->index,
			'type' => $this->makeTypeString($this->index, $children_index_models),
			'body' => [
				'query' => [
					'bool' => [
						'should' => [
							[
								'term' => [
									'_parent' => $this->index_type.'#'.$id
								]
							],
							[
								'ids' => [
									'type' => $this->index_type,
									'values' => [$id]
								]
							]
						]
					]
				]
			]
		];

		return $params;
	}
	
	public function errors()
	{
		return $this->errors;
	}
}