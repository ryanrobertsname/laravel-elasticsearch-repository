<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\ParamsGenerator\Index;

use Illuminate\Support\MessageBag;

/**
* Class
*/
class ESBulkIndexParamsGenerator implements BulkIndexParamsGeneratorInterface
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
	 * Make Bulk Index Params
	 * @param  string $index_model 
	 * @param  array  $items      Will accomodate "_id" and "_parent_id" keys along with normal doc fields
	 * @return array
	 */
	public function makeParams($index_model, array $items)
	{
		$this->getConfig($index_model);

		$body = '';

		foreach ($items as $item):

			//extract id if present
			$result = $this->pullId($item);
			$item = $result[0];
			$id = $result[1];

			//extract parent if present
			$result = $this->pullParent($item);
			$item = $result[0];
			$parent = $result[1];

			//validate index fields
			$this->validateFields($item);

			$body .= $this->makeBulkEntry($id, $item, $parent);

		endforeach;

		$params = [
			'index' => $this->index,
			'type' => $this->index_type,
			'body' => $body
		];

		return $params;
	}

	public function makeBulkEntry($id, array $fields, $parent = null)
	{
			$define['index']['_id'] = $id;
			
			if ($parent)
				$define['index']['_parent'] = $parent;
			
			$define = json_encode($define);
			$fields = json_encode($fields);

			return $define."\n".$fields."\n";
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
}