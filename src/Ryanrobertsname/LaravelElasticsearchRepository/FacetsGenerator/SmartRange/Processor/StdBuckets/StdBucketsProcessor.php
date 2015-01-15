<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\ProcessorBase;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

/**
* Class
*/
class StdBucketsProcessor extends ProcessorBase implements StdBucketsProcessorInterface
{
	protected $bucket_labeler;
	protected $max_bucket_range;
	protected $std_buckets = []; 
	protected $raw_high_buckets = [];
	protected $compiled_low_buckets = [];
	protected $max_facets;
	protected $filter_field_name;

	public function make(BucketLabelerInterface $bucket_labeler, $filter_field_name, $max_facets, $max_bucket_range, array $raw_std_buckets, array $compiled_low_buckets, array $raw_high_buckets)
	{
		if (empty($max_bucket_range) || !is_integer($max_bucket_range) || empty($max_facets) || !is_integer($max_facets))
			throw new \InvalidArgumentException();

		$this->bucket_labeler = $bucket_labeler;
		$this->std_buckets = $raw_std_buckets;
		$this->compiled_low_buckets = $compiled_low_buckets;
		$this->raw_high_buckets = $raw_high_buckets;
		$this->max_facets = $max_facets;
		$this->max_bucket_range = $max_bucket_range;
		$this->filter_field_name = $filter_field_name;

		return $this->processStdBuckets();
	}
	
	protected function processStdBuckets()
	{
		if (empty($this->std_buckets)) return [];

		$std_buckets = [];

		foreach ($this->std_buckets as $bucket):
			$new_bucket = [
				'label' => '',
				'count' => $bucket['doc_count'],
				'min' => $bucket['key'],
				'max' => ($bucket['key'] + $this->max_bucket_range)
			];
			$std_buckets[] = $new_bucket;	
		endforeach;

		$std_buckets = $this->makeCompressedBuckets($std_buckets);

		if (empty($this->compiled_low_buckets)):
			$std_buckets = $this->makeStdBucketsStartAtZero($std_buckets);
		else:
			$std_buckets = $this->makeStdBucketsStartWhereLowBucketLeavesOff($std_buckets);
		endif;

		if (empty($this->raw_high_buckets))
			$std_buckets = $this->makeStdBucketsEndAtInfinity($std_buckets);

		return $this->addBucketLabelsAndFilters($this->closeBucketGaps($std_buckets));

	}

	public function addBucketLabelsAndFilters(array $buckets)
	{
		foreach ($buckets as $key => $bucket):
			$buckets[$key]['filter'] = $this->makeBucketfilter($bucket);
			$buckets[$key]['label'] = $this->bucket_labeler->makeBucketLabel($bucket);
		endforeach;

		return $buckets;
	}

	protected function closeBucketGaps(array $buckets)
	{
		$prev_max = null;

		foreach ($buckets as $key => $bucket):

			if ($bucket['min'] != $prev_max && !is_null($prev_max)):
				
				$price_meet = $this->calcBucketGapMeetPrice($prev_max, $bucket['min']);

				$buckets[$key]['min'] = $price_meet;

				$buckets[$key - 1]['max'] = $price_meet;

			endif;

			$prev_max = $bucket['max'];

		endforeach;

		return $buckets;
	}

	protected function calcBucketGapMeetPrice($price_gap_start, $price_gap_end)
	{
		$price_gap = $price_gap_end - $price_gap_start;
		$price_gap_median = $price_gap_start + ($price_gap / 2);
		$price_meet_amount = (floor($price_gap_median / $this->max_bucket_range) * $this->max_bucket_range);

		return (int) $price_meet_amount;
	}

	protected function makeStdBucketsStartAtZero(array $buckets)
	{
		$buckets[0]['min'] = null;

		return $buckets;
	}

	protected function makeStdBucketsStartWhereLowBucketLeavesOff(array $buckets)
	{
		$buckets[0]['min'] = $this->compiled_low_buckets['max'];

		return $buckets;		
	}	

	protected function makeStdBucketsEndAtInfinity(array $buckets)
	{
		$last_key = count($buckets) - 1;
		$buckets[$last_key]['max'] = null;

		return $buckets;		
	}

	protected function makeCompressedBuckets(array $buckets)
	{
		$bucket_count = count($buckets);
		$num_of_compressions_needed = $bucket_count - $this->max_facets;

		if (!empty($this->compiled_low_buckets))
			$num_of_compressions_needed += 1;

		if (!empty($this->raw_high_buckets))
			$num_of_compressions_needed += 1;
		
		if ($num_of_compressions_needed <= 0) return $buckets;

		return $this->compressBuckets($buckets, $num_of_compressions_needed);
	}

	protected function compressBuckets(array $buckets, $compress_times)
	{
		if (!is_integer($compress_times)) throw new \InvalidArgumentException('Compression number argument must be an integer');

		for ($i = 1; $i <= $compress_times; $i++)
			$buckets = $this->mergeSmallestBucketToClosestPeer(array_reverse($buckets));

		//due to alternating the starting point for the merge operation, 
		//ensure the buckets are in the correct order before returning
		$buckets = $this->orderBuckets($buckets);

		return $buckets;
	}

	protected function orderBuckets(array $buckets)
	{
		$first_element = reset($buckets);
		$second_element = next($buckets);

		if ($first_element['min'] > $second_element['min'])
			return array_reverse($buckets);

		return $buckets;
	}

	protected function mergeSmallestBucketToClosestPeer(array $buckets)
	{
		if (count($buckets) < 2) throw new \InvalidArgumentException('There must be at least two buckets for this merge operation');

		$smallest_key = null;
		$smallest_count = null;
		$prev_count = null;
		$prev_distance = null;
		$next_count = null;
		$next_distance = null;

		//find smallest
		foreach ($buckets as $key => $bucket)
			if (is_null($smallest_key) || $bucket['count'] < $smallest_count):
				$smallest_key = $key;
				$smallest_count = $bucket['count'];
			endif;

		//get bucket prev to smallest if it exists
		if ($smallest_key > 0):
			$prev_count = $buckets[$smallest_key - 1]['count'];
			$prev_distance = ($buckets[$smallest_key]['min'] - $buckets[$smallest_key - 1]['max']);
		endif;

		//get bucket next after smallest if it exists
		if (isset($buckets[$smallest_key + 1])):
			$next_count = $buckets[$smallest_key + 1]['count'];
			$next_distance = ($buckets[$smallest_key + 1]['min'] - $buckets[$smallest_key]['max']);
		endif;

		//combine smallest bucket with previous
		if (!is_null($prev_count) && ($prev_count <= $next_count || is_null($next_count)))
			return $this->combineBuckets($buckets, $smallest_key, $smallest_key - 1);

		//combine smallest bucket with next
		if (!is_null($next_count) && ($next_count < $prev_count || is_null($prev_count)))
			return $this->combineBuckets($buckets, $smallest_key, $smallest_key + 1);
		
		throw new \RuntimeException('An error occured when merging price facet buckets');
	}

	protected function combineBuckets(array $buckets, $from_bucket_key, $to_bucket_key)
	{
		if (!is_integer($from_bucket_key) || !is_integer($to_bucket_key)) throw new \InvalidArgumentException('Bucket key args must be integers');

		if ($buckets[$from_bucket_key]['min'] < $buckets[$to_bucket_key]['min'])
			$buckets[$to_bucket_key]['min'] = $buckets[$from_bucket_key]['min'];

		if ($buckets[$from_bucket_key]['max'] > $buckets[$to_bucket_key]['max'])
			$buckets[$to_bucket_key]['max'] = $buckets[$from_bucket_key]['max'];

		$buckets[$to_bucket_key]['count'] += $buckets[$from_bucket_key]['count'];

		unset($buckets[$from_bucket_key]);
		
		return array_values($buckets);
	}
}