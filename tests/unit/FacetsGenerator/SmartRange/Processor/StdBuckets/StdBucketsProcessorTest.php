<?php 

/**
* Class
*/
class StdBucketsProcessorTest extends TestCase
{
	protected $std_buckets = [];

	public function setUp()
	{
		parent::setUp();

		$this->std_buckets = [
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

		$this->std_buckets_with_even_gap = [
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
				'key' => 16000,
				'doc_count' => 30
			],
			[
				'key' => 17000,
				'doc_count' => 2
			],
			[
				'key' => 50000,
				'doc_count' => 1
			],
		];

		$this->std_buckets_with_odd_gap = [
			[
				'key' => 5000,
				'doc_count' => 1
			],
			[
				'key' => 10000,
				'doc_count' => 10
			],
			[
				'key' => 11500,
				'doc_count' => 40
			],
			[
				'key' => 16500,
				'doc_count' => 30
			],
			[
				'key' => 17000,
				'doc_count' => 2
			],
			[
				'key' => 50000,
				'doc_count' => 1
			],
		];

		$this->bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
	}

	public function testMakeReturnsEmptyArrayWhenStdBucketsArgIsEmpty()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, [], [], []);

		assertThat($result, identicalTo([]));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowAndHighBucketsDontExistAndMaxFacetsIsLessThanBuckets()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $120',
				'count' => 40,
				'min' => 11000,
				'max' => 12000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 12000
					]
				]
			],
			[
				'label' => '$120 &#8211; $130',
				'count' => 30,
				'min' => 12000,
				'max' => 13000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 12000,
						'max' => 13000
					]
				]
			],
			[
				'label' => '$130 &#43;',
				'count' => 3,
				'min' => 13000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 13000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets, [], []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets));
		assertThat($result, identicalTo($expected_result));
	}

	/**
	 * This test is for a bug fix, commit 37ab32b4baf2893bdf0840a2baccc6409e175ea1
	 * If compression was taking place, and if the previous and next buckets surrounding the bucket with the lowest count
	 * had the same count (prev and next buckets have same count) = exception thrown.
	 * Fix in place to ensure this doesnt happen, defaults to using the previous bucket for compression if previous and next
	 * have same count.
	 */
	public function testMakeDoesNotErrorOutWhenCompressionTakesPlace()
	{
		$std_buckets = [
			[
				'key' => 5000,
				'doc_count' => 10
			],
			[
				'key' => 10000,
				'doc_count' => 10
			],
			[
				'key' => 11000,
				'doc_count' => 1
			],
			[
				'key' => 12000,
				'doc_count' => 10
			],
			[
				'key' => 13000,
				'doc_count' => 10
			],
			[
				'key' => 50000,
				'doc_count' => 10
			],
		];

		$max_facets = 4;
		$bucket_interval = 1000;

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 20,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $130',
				'count' => 11,
				'min' => 11000,
				'max' => 13000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 13000
					]
				]
			],
			[
				'label' => '$130 &#8211; $320',
				'count' => 10,
				'min' => 13000,
				'max' => 32000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 13000,
						'max' => 32000
					]
				]
			],
			[
				'label' => '$3200 &#43;',
				'count' => 10,
				'min' => 32000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 32000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$this->bucket_label_mock->shouldReceive('makeBucketLabel')->andReturn('test');

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $std_buckets, [], []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowAndHighBucketsDontExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHaveEvenPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#8211; $170',
				'count' => 30,
				'min' => 14000,
				'max' => 17000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => 17000
					]
				]
			],
			[
				'label' => '$170 &#43;',
				'count' => 3,
				'min' => 17000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 17000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_even_gap, [], []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowAndHighBucketsDontExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHasOddPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 500;

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#8211; $170',
				'count' => 30,
				'min' => 14000,
				'max' => 17000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => 17000
					]
				]
			],
			[
				'label' => '$170 &#43;',
				'count' => 3,
				'min' => 17000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 17000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_odd_gap, [], []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenHighBucketsDontExistAndMaxFacetsIsLessThanBuckets()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$low_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => null,
			'max' => 8000,				
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => null,
					'max' => 8000
				]
			]
		];

		$expected_result = [
			[
				'label' => '$80 &#8211; $110',
				'count' => 11,
				'min' => 8000,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 8000,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $120',
				'count' => 40,
				'min' => 11000,
				'max' => 12000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 12000
					]
				]
			],
			[
				'label' => '$120 &#43;',
				'count' => 33,
				'min' => 12000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 12000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets, $low_bucket_mock, []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenHighBucketsDontExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHaveEvenPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$low_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => null,
			'max' => 8000,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => null,
					'max' => 8000
				]
			]
		];

		$expected_result = [
			[
				'label' => '$80 &#8211; $110',
				'count' => 11,
				'min' => 8000,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 8000,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#43;',
				'count' => 33,
				'min' => 14000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_even_gap, $low_bucket_mock, []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenHighBucketsDontExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHasOddPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$low_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => null,
			'max' => 8000,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => null,
					'max' => 8000
				]
			]
		];

		$expected_result = [
			[
				'label' => '$80 &#8211; $110',
				'count' => 11,
				'min' => 8000,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 8000,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#43;',
				'count' => 33,
				'min' => 14000,
				'max' => null,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => null
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_odd_gap, $low_bucket_mock, []);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowBucketDoesntExistAndMaxFacetsIsLessThanBuckets()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$high_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => 60000,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 60000,
					'max' => null
				]
			]
		];

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $120',
				'count' => 40,
				'min' => 11000,
				'max' => 12000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 12000
					]
				]
			],
			[
				'label' => '$120 &#8211; $510',
				'count' => 33,
				'min' => 12000,
				'max' => 51000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 12000,
						'max' => 51000
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets, [], $high_bucket_mock);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowBucketDoesntExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHaveEvenPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$high_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => 60000,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 60000,
					'max' => null
				]
			]
		];

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#8211; $510',
				'count' => 33,
				'min' => 14000,
				'max' => 51000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => 51000
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_even_gap, [], $high_bucket_mock);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}

	public function testMakeProcessesBucketsCorrectlyAndReturnsResultWhenLowBucketDoesntExistAndMaxFacetsIsLessThanBucketsAndStdBucketsHasOddPriceGap()
	{
		$max_facets = 4;
		$bucket_interval = 1000;

		$high_bucket_mock = [
			'label' => 'test label',
			'count' => 1,
			'min' => 60000,
			'max' => null,
			'filter' => [
				'filter' => 'NumberRangeFilter',
				'params' => [
					'field' => 'min_price',
					'min' => 60000,
					'max' => null
				]
			]
		];

		$expected_result = [
			[
				'label' => '$0 &#8211; $110',
				'count' => 11,
				'min' => null,
				'max' => 11000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => null,
						'max' => 11000
					]
				]
			],
			[
				'label' => '$110 &#8211; $140',
				'count' => 40,
				'min' => 11000,
				'max' => 14000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 11000,
						'max' => 14000
					]
				]
			],
			[
				'label' => '$140 &#8211; $510',
				'count' => 33,
				'min' => 14000,
				'max' => 51000,
				'filter' => [
					'filter' => 'NumberRangeFilter',
					'params' => [
						'field' => 'min_price',
						'min' => 14000,
						'max' => 51000
					]
				]
			]
		];

		foreach ($expected_result as $result)
			$this->bucket_label_mock->shouldReceive('makeBucketLabel')->with(['min' => $result['min'], 'max' => $result['max'], 'count' => $result['count'], 'label' => ''])->andReturn($result['label']);

		$processor = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor');
		$result = $processor->make($this->bucket_label_mock, 'min_price', $max_facets, $bucket_interval, $this->std_buckets_with_odd_gap, [], $high_bucket_mock);
		$count = count($result);

		assertThat($count, identicalTo($max_facets - 1));
		assertThat($result, identicalTo($expected_result));
	}
}
