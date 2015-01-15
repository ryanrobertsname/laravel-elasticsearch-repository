<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\ProcessorBase;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

/**
* Class
*/
class HighBucketProcessor extends ProcessorBase implements HighBucketProcessorInterface
{
	protected $bucket_labeler;
	protected $raw_high_buckets = [];
	protected $filter_field_name;

	/**
	 * Compile high buckets into single ready to use high bucket
	 * @param  $bucket_labeler
	 * @param  $field_name
	 * @param  array  $raw_high_buckets raw buckets above high cutoff price
	 * @param  array  $std_buckets         processed std buckets
	 * @param  array  $low_bucket          processed low bucket
	 * @return array                      
	 */
	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, array $raw_high_buckets, array $compiled_low_buckets, array $processed_std_buckets)
	{
		$this->bucket_labeler = $bucket_labeler;
		$this->raw_high_buckets = $raw_high_buckets;
		$this->filter_field_name = $filter_field_name;

		return $this->compileHighCutoffBuckets($processed_std_buckets, $compiled_low_buckets);
	}

	protected function compileHighCutoffBuckets(array $std_buckets, array $low_bucket)
	{
		if (empty($this->raw_high_buckets)) return [];

		$high_bucket = [
			'label' => '',
			'count' => 0,
			'min' => $this->calcHighBucketMinPrice($std_buckets, $low_bucket),
			'max' => null,
			'filter' => null
		];

		foreach ($this->raw_high_buckets as $bucket):

			$high_bucket['count'] += $bucket['doc_count'];

		endforeach;

		$high_bucket['filter'] = $this->makeBucketfilter($high_bucket);

		$high_bucket['label'] = $this->bucket_labeler->makeBucketLabel($high_bucket);

		return $high_bucket;
	}

	/**
	 * Calculate high bucket's min price, use max price from std buckets if exists,
	 * if not use max price for low bucket if exists, if not return null (which will represent a 0 price point)
	 * @param  array  $std_buckets 
	 * @param  array  $low_bucket  
	 * @return int|null              
	 */
	protected function calcHighBucketMinPrice(array $std_buckets, array $low_bucket)
	{
		$last_std_bucket = array_pop($std_buckets);

		if (!empty($last_std_bucket['max']))
			return $last_std_bucket['max'];

		if (!empty($low_bucket['max']))
			return $low_bucket['max'];

		return null;
	}
}