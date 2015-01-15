<?php 

/**
* Class
*/
class LowBucketProcessorTest extends TestCase
{
	protected $low_buckets = [];

	public function setUp()
	{
		parent::setUp();

		\Config::shouldReceive('get')->with('index.es_product_index_price_range_interval')->andReturn(1000);

		$this->low_buckets = [
			[
				'key' => 5000,
				'doc_count' => 1
			],
			[
				'key' => 10000,
				'doc_count' => 10
			],
			[
				'key' => 11000,
				'doc_count' => 40
			],
			[
				'key' => 12000,
				'doc_count' => 30
			],
			[
				'key' => 13000,
				'doc_count' => 2
			],
			[
				'key' => 50000,
				'doc_count' => 1
			],
		];

		$this->bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
	}

	public function testMakeReturnsEmptyArrayWhenLowBucketsAreEmpty()
	{
		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', 1000, [], 1);

		assertThat($result, identicalTo([]));
	}

	public function testMakeCompilesBucketsCorrectlyAndReturnsResult()
	{
		$expected_result = [
			'label' => '$0 &#8211; $510',
			'count' => 84,
			'min' => null,
			'max' => 51000,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => null,
					'max' => 51000
				]
			]
		];

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $expected_result['min'], 'max' => $expected_result['max'], 'count' => $expected_result['count'], 'filter' => $expected_result['filter'], 'label' => ''])->andReturn($expected_result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', 1000, $this->low_buckets, 1000);

		assertThat($result, identicalTo($expected_result));
	}
}