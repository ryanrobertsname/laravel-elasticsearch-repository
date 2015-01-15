<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\AbstractFacetGenerator;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\SmartRangeGeneratorInterface;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface;
use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface;

/**
* Class that dyanmically handles the generation of ranged facets
* 
* Uses standard deviation to generate ranged facets most applicable to the population of items.
* Generates ranged facets without gaps that represents the population of items.
*
* Uses aggregation data form NumberRangeAgg, provides NumberRangeFilter params to each facet for client to
* request filtered facet result
*/
class ESSmartRangeGenerator extends AbstractFacetGenerator implements SmartRangeGeneratorInterface
{
	protected $bucket_labeler;
	protected $high_bucket_processor;
	protected $std_buckets_processor;
	protected $low_bucket_processor;
	protected $low_bucket_cutoff;
	protected $high_bucket_cutoff;
	protected $buckets;

	public function __construct(HighBucketProcessorInterface $high_bucket_processor, StdBucketsProcessorInterface $std_buckets_processor, LowBucketProcessorInterface $low_bucket_processor)
	{
		$this->high_bucket_processor = $high_bucket_processor;
		$this->std_buckets_processor = $std_buckets_processor;
		$this->low_bucket_processor = $low_bucket_processor;
	}

	protected function setUpProperties()
	{
		$this->buckets = [
			'low_cutoff' => [],
			'std' => [],
			'high_cutoff' => []
		];
		$this->low_bucket_cutoff = null;
		$this->high_bucket_cutoff = null;
	}

	/**
	 * Make SmartRange facets
	 * @param  string $bucket_label_type type of bucket label, corresponds to labeler class name
	 * @param  string $filter_field_name
	 * @param  int $max_facets      Max number of facets
	 * @param  int $max_bucket_range Max range of each bucket included in $buckets argument (aka interval).
	 * @param  array  $stats           extended aggregation stats
	 * @param  array  $buckets         aggregated buckets
	 * @return array                  SmartRanged facets
	 */
	public function make($bucket_label_type, $filter_field_name, $max_facets, $max_bucket_range, array $stats, array $buckets)
	{
		if (empty($filter_field_name) || empty($max_facets) || empty($max_bucket_range) || empty($stats)
			|| !is_integer($max_facets) || !is_integer($max_bucket_range))
				throw new \InvalidArgumentException();

		//clear / setup properties
		$this->setUpProperties();

		//This path should end at a folder destination, Service Provider will determine the correct implementation to return
		$this->bucket_labeler = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\\'.$bucket_label_type);
		$this->calcBucketCutOffs($stats);
		$this->assignBuckets($buckets);

		return $this->makeResponse($filter_field_name, $max_facets, $max_bucket_range);
	}

	protected function makeResponse($filter_field_name, $max_facets, $max_bucket_range)
	{
		$low_bucket = $this->low_bucket_processor->make($this->bucket_labeler, $filter_field_name, $max_bucket_range, $this->buckets['low_cutoff']);
		$std_buckets = $this->std_buckets_processor->make($this->bucket_labeler, $filter_field_name, $max_facets, $max_bucket_range, $this->buckets['std'], $low_bucket, $this->buckets['high_cutoff']);
		$high_bucket = $this->high_bucket_processor->make($this->bucket_labeler, $filter_field_name, $this->buckets['high_cutoff'], $low_bucket, $std_buckets);

		$response = [];	

		if ($low_bucket)
			$response[] = $low_bucket;

		if ($std_buckets)
			$response = array_merge($response, $std_buckets);

		if ($high_bucket)
			$response[] = $high_bucket;

		return $this->cleanFacet($response);
	}	
	
	protected function assignBuckets(array $price_buckets)
	{
		foreach ($price_buckets as $bucket):

			if ($bucket['key'] < $this->low_bucket_cutoff):
				$this->buckets['low_cutoff'][] = $bucket;
				continue;
			endif;

			if ($bucket['key'] > $this->high_bucket_cutoff):
				$this->buckets['high_cutoff'][] = $bucket;
				continue;
			endif;

			$this->buckets['std'][] = $bucket;

		endforeach;

		return true;
	}

	protected function calcBucketCutOffs(array $stats)
	{
		$this->low_bucket_cutoff = (int) $stats['avg'] - (int) $stats['std_deviation'];
		$this->high_bucket_cutoff = (int) $stats['avg'] + (int) $stats['std_deviation'];

		return true;
	}
}