<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange;

interface SmartRangeGeneratorInterface
{
	public function make($bucket_label_type, $filter_field_name, $max_facets, $max_bucket_range, array $stats, array $buckets);			
}