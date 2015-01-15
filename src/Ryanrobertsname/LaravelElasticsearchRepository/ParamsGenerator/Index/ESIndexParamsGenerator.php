<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index;

use Illuminate\Support\MessageBag;

/**
* Class
*/
class ESIndexParamsGenerator implements IndexParamsGeneratorInterface
{
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
		
		if (!\Config::has($config_var))
			throw new \InvalidArgumentException('Index model cannot be found in config');

		$this->config = \Config::get($config_var);

		$this->index = $this->config['setup']['index'];
		$this->index_type = $this->config['setup']['type'];
	}

	/**
	 * make indexing params for elasticsearch
	 * @param  string  $index_model
	 * @param  array $item  --contains doc fields and _id, and possibly _parent_id
	 * @return 
	 */
	public function makeParams($index_model, array $item)
	{
		$this->getConfig($index_model);

		//extract id if present
		$result = $this->pullId($item);
		$item = $result[0];
		$id = $result[1];

		//extract parent if present
		$result = $this->pullParent($item);
		$item = $result[0];
		$parent_id = $result[1];

		//validate remaining index fields
		$this->validateFields($item);

		$params = [
			'index' => $this->index,
			'type' => $this->index_type,
			'id' => $id,
			'body' => $item
		];

		//add parent id to params if needed
		$params = $this->addParentIdParam($params, $parent_id);

		return $params;
	}

	protected function addParentIdParam(array $params, $parent_id)
	{
		if (empty($this->config['setup']['mapping']['parent']))
			return $params;

		if (empty($parent_id))
			throw new \InvalidArgumentException('parent id required');

		$params['parent'] = $parent_id;

		return $params;
	}

	protected function validateFields(array $fields)
	{
		$this->validateFieldAvailibility($fields);
	}

	protected function validateFieldAvailibility(array $fields)
	{
		foreach ($fields as $field => $field_value)
			if (!isset($this->config['setup']['mapping']['properties'][$field]))
				throw new \InvalidArgumentException($field.' field is not mapped in this index / type');
	}

	protected function pullId(array $item)
	{
		if (empty($item['_id']))
			throw new \InvalidArgumentException('Id field required');

		$id = array_pull($item, '_id');

		return [$item, $id];
	}

	protected function pullParent(array $fields)
	{
		if (empty($this->config['setup']['mapping']['parent']))
			return [$fields, null];

		if (empty($fields['_parent_id']))
			throw new \InvalidArgumentException('parent required');

		$parent = array_pull($fields, '_parent_id');

		return [$fields, $parent];
	}
}