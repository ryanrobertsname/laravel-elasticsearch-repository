<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\Repository;

interface IndexRepositoryInterface
{
	public function model($index_model);
	public function index(array $params, $refresh = false);
	public function updateIndex(array $params, $refresh = false);
	public function bulkIndex(array $params, $refresh = false);
	public function delete($id, $refresh = false);	
	public function search($search_type, $query, $offset, $limit, array $filters = [], array $fields = []);
	public function mltSearch($id, $limit);
	public function deleteSelfAndChildren($self_id, array $children_index_models);
}