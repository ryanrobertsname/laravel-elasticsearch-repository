<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

interface HighBucketProcessorInterface
{
	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, array $raw_high_buckets, array $compiled_low_buckets, array $processed_std_buckets);
}