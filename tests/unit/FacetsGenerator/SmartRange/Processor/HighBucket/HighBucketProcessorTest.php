<?php 

/**
* Class
*/
class HighBucketProcessorTest extends TestCase
{
	protected $high_buckets = [];

	public function setUp()
	{
		parent::setUp();

		\Config::shouldReceive('get')->with('index.es_product_index_range_interval')->andReturn(1000);

		$this->high_buckets = [
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

	public function testMakeReturnsEmptyArrayWhenHighBucketsAreEmpty()
	{
		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->andReturn(true);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', [], [], []);

		assertThat($result, identicalTo([]));
	}

	public function testMakeCompilesBucketsCorrectlyAndReturnsResultWhenNoStdBucketsOrLowBucketsAreProvided()
	{
		$expected_result = [
			'label' => '$0 &#43;',
			'count' => 84,
			'min' => null,
			'max' => null,
			'filter' => null
		];

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $expected_result['min'], 'max' => $expected_result['max'], 'count' => $expected_result['count'], 'filter' => $expected_result['filter'], 'label' => ''])->andReturn($expected_result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $this->high_buckets, [], []);

		assertThat($result, identicalTo($expected_result));
	}
	
	public function testMakeCompilesBucketsCorrectlyAndReturnsResultWhenNoStdBucketsAreProvided()
	{
		$this->high_buckets = [
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

		$compiled_low_bucket = [
			'label' => 'label test',
			'count' => 3,
			'min' => 10,
			'max' => 1000,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 10,
					'max' => 1000
				]
			]
		];

		$expected_result = [
			'label' => '$10 &#43;',
			'count' => 84,
			'min' => 1000,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 1000,
					'max' => null
				]
			]
		];

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $expected_result['min'], 'max' => $expected_result['max'], 'count' => $expected_result['count'], 'filter' => $expected_result['filter'], 'label' => ''])->andReturn($expected_result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $this->high_buckets, $compiled_low_bucket, []);

		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeCompilesBucketsCorrectlyAndReturnsResultWhenLowBucketIsNotProvided()
	{
		$this->high_buckets = [
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

		$processed_std_buckets = [
			[
				'label' => 'label test',
				'count' => 3,
				'min' => 10,
				'max' => 1000,
				'filter' => [
					'filter' => 'NumberRangeFilter',

					'params' => [
						'field' => 'min_price',
						'min' => 10,
						'max' => 1000
					]
				]
			],
			[
				'label' => 'label test',
				'count' => 2,
				'min' => 1000,
				'max' => 1100,
				'filter' => [
					'filter' => 'NumberRangeFilter',

					'params' => [
						'field' => 'min_price',
						'min' => 1000,
						'max' => 1100
					]
				]
			]			
		];

		$expected_result = [
			'label' => '$11 &#43;',
			'count' => 84,
			'min' => 1100,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 1100,
					'max' => null
				]
			]
		];

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $expected_result['min'], 'max' => $expected_result['max'], 'count' => $expected_result['count'], 'filter' => $expected_result['filter'], 'label' => ''])->andReturn($expected_result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $this->high_buckets, [], $processed_std_buckets);

		assertThat($result, identicalTo($expected_result));
	}

public function testMakeCompilesBucketsCorrectlyAndReturnsResultWhenLowAndStdBucketsAreProvided()
	{
		$this->high_buckets = [
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

		$processed_std_buckets = [
			[
				'label' => 'label test',
				'count' => 3,
				'min' => 10,
				'max' => 1000,
				'filter' => [
					'filter' => 'NumberRangeFilter',

					'params' => [
						'field' => 'min_price',
						'min' => 10,
						'max' => 1000
					]
				]
			],
			[
				'label' => 'label test',
				'count' => 2,
				'min' => 1000,
				'max' => 1100,
				'filter' => [
					'filter' => 'NumberRangeFilter',

					'params' => [
						'field' => 'min_price',
						'min' => 1000,
						'max' => 1100
					]
				]
			]			
		];

		$compiled_low_bucket = [
			'label' => 'label test',
			'count' => 3,
			'min' => 10,
			'max' => 1000,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 10,
					'max' => 1000
				]
			]
		];

		$expected_result = [
			'label' => '$11 &#43;',
			'count' => 84,
			'min' => 1100,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 1100,
					'max' => null
				]
			]
		];

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $expected_result['min'], 'max' => $expected_result['max'], 'count' => $expected_result['count'], 'filter' => $expected_result['filter'], 'label' => ''])->andReturn($expected_result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $this->high_buckets, $compiled_low_bucket, $processed_std_buckets);

		assertThat($result, identicalTo($expected_result));
	}
}