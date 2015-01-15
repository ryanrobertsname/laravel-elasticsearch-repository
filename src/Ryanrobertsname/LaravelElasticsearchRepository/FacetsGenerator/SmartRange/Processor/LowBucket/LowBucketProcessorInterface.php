<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

interface LowBucketProcessorInterface
{
	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, $max_bucket_rante, array $raw_low_buckets);
}