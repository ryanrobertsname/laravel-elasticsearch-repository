<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\ProcessorBase;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

/**
* Class
*/
class LowBucketProcessor extends ProcessorBase implements LowBucketProcessorInterface
{
	protected $bucket_labeler;
	protected $max_bucket_range;
	protected $raw_low_buckets = [];
	protected $filter_field_name;

	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, $max_bucket_range, array $raw_low_buckets)
	{
		if (empty($max_bucket_range) || !is_integer($max_bucket_range))
			throw new \InvalidArgumentException();

		$this->bucket_labeler = $bucket_labeler;
		$this->raw_low_buckets = $raw_low_buckets;
		$this->max_bucket_range = $max_bucket_range;
		$this->filter_field_name = $filter_field_name;

		return $this->compileLowCutoffBuckets();
	}
	
	protected function compileLowCutoffBuckets()
	{		
		if (empty($this->raw_low_buckets)) return [];

		$low_bucket = [
			'label' => '',
			'count' => 0,
			'min' => null,
			'max' => '',
			'filter' => null
		];

		foreach ($this->raw_low_buckets as $bucket):

			if (empty($low_bucket['max']) || ($bucket['key'] + $this->max_bucket_range) > $low_bucket['max'])
				$low_bucket['max'] = ($bucket['key'] + $this->max_bucket_range);

			$low_bucket['count'] += $bucket['doc_count'];

		endforeach;

		$low_bucket['filter'] = $this->makeBucketfilter($low_bucket);
		
		$low_bucket['label'] = $this->bucket_labeler->makeBucketLabel($low_bucket);

		return $low_bucket;
	}
}