<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Delete;

use Illuminate\Support\MessageBag;

/**
* Class
*/
class ESDeleteParamsGenerator implements ESDeleteParamsGeneratorInterface
{
	protected $errors;
	protected $config;
	protected $index;
	protected $index_type;

	public function __construct(MessageBag $message_bag)
	{
		$this->errors = $message_bag;
	}

	protected function getConfig($index_model)
	{
		$config_var = 'laravel-elasticsearch-repository::index.index_models.'.$index_model;
		$this->config = \Config::get($config_var);

		if (empty($this->config))
			throw new \InvalidArgumentException('Index model cannot be found in config file.');

		$this->index = $this->config['setup']['index'];
		$this->index_type = $this->config['setup']['type'];
	}

	public function makeParams($index_model, $id, $refresh = false)
	{
		if (empty($id) || !is_integer($id))
			throw new \InvalidArgumentException();

		$this->getConfig($index_model);

		$params = [
			'index' => $this->index,
			'type' => $this->index_type,
			'id' => $id
		];

		if ($refresh)
			$params['refresh'] = true;

		return $params;
	}
	
	public function errors()
	{
		return $this->errors;
	}
}