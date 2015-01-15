<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel;

interface BucketLabelerInterface
{
	public function makeBucketLabel(array $bucket);
}