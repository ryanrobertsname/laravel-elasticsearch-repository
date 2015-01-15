<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

interface StdBucketsProcessorInterface
{
	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, $max_facets, $max_bucket_range, array $raw_std_buckets, array $compiled_low_buckets, array $raw_high_buckets);
}