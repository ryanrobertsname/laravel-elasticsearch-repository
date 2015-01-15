<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor;

/**
* Class
*/
class ProcessorBase
{
	protected function makeBucketFilter(array $bucket)
	{
		if (is_null($bucket['max']) && is_null($bucket['min']))
			return null;

		$filter =  
		[
			'filter' => 'NumberRangeFilter',
			'params' => [
				'field' => $this->filter_field_name,
				'min' => $bucket['min'],
				'max' => $bucket['max']
			]
		];

		return $filter;
	}
}