<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Repository;

trait BaseTrait {
	
	/**
	 * add to index, replace existing docs with same id
	 * @param  array   $params             
	 * @param  boolean $refresh            should index refresh immediately?
	 * @return boolean                      
	 */
	public function index(array $params, $refresh = false)
	{
		return $this->indexDoc($params, $refresh);
	}

	/**
	 * update existing doc in index, uses regular index params generator
	 * @param  array   $params             
	 * @param  boolean $refresh            should index refresh immediately?
	 * @return boolean                      
	 */
	public function updateIndex(array $params, $refresh = false)
	{
		return $this->indexDoc($params, $refresh, false);
	}

	/**
	 * add to or update index
	 * @param  array   $params             
	 * @param  boolean $refresh            should index refresh immediately?
	 * @param  boolean $replace_existing   should this entry replace the existing doc in its entirety? otherewise it will update the existing doc
	 * @return boolean                      
	 */
	protected function indexDoc(array $params, $refresh = false, $replace_existing = true)
	{

		$params = $this->index_params_generator->makeParams($this->index_model, $params);

		//make index refresh if needed
		if ($refresh)
			$params['refresh'] = true;

		if ($replace_existing):
			$response = $this->clientIndex($params);
		else:
			$response = $this->clientUpdate($params);
		endif;

		//return true if no errors are found / logged, false otherwise;
		return !$this->logOpErrors($response, 'index_operation');
	}

	protected function clientIndex($params)
	{
		return $this->es_client->index($params);
	}

	protected function clientUpdate($params)
	{
		//wrap body params with "doc" to fit update api
		$body = array_pull($params, 'body');
		$params['body']['doc'] = $body;

		return $this->es_client->update($params);
	}

	public function bulkIndex(array $params, $refresh = false)
	{
		$params = $this->bulk_index_params_generator->makeParams($this->index_model, $params);

		//make index refresh if needed
		if ($refresh)
			$params['refresh'] = true;

		$response = $this->clientBulkIndex($params);

		//return true if no errors are found / logged, false otherwise;
		return !$this->logOpErrors($response, 'bulk_index_operation');
	}

	protected function clientBulkIndex($params)
	{
		return $this->es_client->bulk($params);
	}

	protected function logOpErrors($response, $op_name)
	{
		if (!empty($response['error'])):
			$this->errors->add($op_name, $response['error']);
			return true;
		endif;

		if (!empty($response['errors'])):
			$this->errors->add($op_name, $response['errors']);
			return true;
		endif;

		return false;
	}

	public function delete($id, $refresh = false)
	{	
		$params = $this->delete_params_generator->makeParams($this->index_model, $id, $refresh);

		$response = $this->clientDelete($params);

		if ($this->logOpErrors($response, 'delete_operation')) return false;

		if (!isset($response['found']))
			throw new \RunTimeException('Document to be deleted does not exist');

		if (!$response['found']) return false;

		return true;
	}

	protected function clientDelete($params)
	{
		return $this->es_client->delete($params);
	}

	protected function clientDeleteByQuery($params)
	{
		return $this->es_client->deleteByQuery($params);
			}

}