<?php 

/**
* Class
*/
class ESSmartRangeGeneratorTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();
	}
	
	public function testMakeSendsCorrectDataToDependenciesAndCompilesCorrectlyWhenThereAreHighLowAndStdBuckets()
	{
		//run test twice to ensure class doesnt cache properties that can effect second run's results
		for ($i = 1; $i < 3; $i++):
		
		$price_stats = [
			'count' => 84,
			'min' => 5100,
			'max' => 50400,
			'avg' => 11600.571428571,
			'sum' => 100000,
			'sum_of_squares' => 0,
			'variance' => 0,
			'std_deviation' => 650.1805975977
		];

		$price_buckets = [
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

		$expected_high_buckets = [
			[
				'key' => 13000,
				'doc_count' => 2
			],
			[
				'key' => 50000,
				'doc_count' => 1
			]
		];

		$high_bucket_return = [
			'high bucket stub'
		];

		$expected_std_buckets = [
			[
				'key' => 11000,
				'doc_count' => 40
			],
			[
				'key' => 12000,
				'doc_count' => 30
			]
		];

		$std_buckets_return = [
			['std bucket stub 1'], 
			['std bucket stub 2']
		];

		$expected_low_buckets = [
			[
				'key' => 5000,
				'doc_count' => 1
			],
			[
				'key' => 10000,
				'doc_count' => 10
			]
		];

		$low_bucket_return = [
			'low bucket stub'
		];

		$bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price', $bucket_label_mock);

		$high_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface');
		$high_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', $expected_high_buckets, $low_bucket_return, $std_buckets_return)->andReturn($high_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface', $high_bucket_mock);
		
		$low_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface');
		$low_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 10000, $expected_low_buckets)->andReturn($low_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface', $low_bucket_mock);

		$std_buckets_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface');
		$std_buckets_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 6, 10000, $expected_std_buckets, $low_bucket_return, $expected_high_buckets)->andReturn($std_buckets_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface', $std_buckets_mock);

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 10000, $price_stats, $price_buckets);

		$expected_response = [
			['low bucket stub'],
			['std bucket stub 1'],
			['std bucket stub 2'],
			['high bucket stub']
		];

		assertThat($response, identicalTo($expected_response));
		
		endfor;
	}

	public function testMakeSendsCorrectDataToDependenciesAndCompilesCorrectlyWhenThereAreOnlyHighAndLowBuckets()
	{
		$price_stats = [
			'count' => 84,
			'min' => 5100,
			'max' => 50400,
			'avg' => 11600.571428571,
			'sum' => 100000,
			'sum_of_squares' => 0,
			'variance' => 0,
			'std_deviation' => 0
		];

		$price_buckets = [
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

		$expected_high_buckets = [
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
			]
		];

		$high_bucket_return = [
			'high bucket stub'
		];

		$expected_std_buckets = [

		];

		$std_buckets_return = [

		];

		$expected_low_buckets = [
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
			]
		];

		$low_bucket_return = [
			'low bucket stub'
		];

		$bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price', $bucket_label_mock);

		$high_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface');
		$high_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', $expected_high_buckets, $low_bucket_return, $std_buckets_return)->andReturn($high_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface', $high_bucket_mock);
		
		$low_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface');
		$low_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 10000, $expected_low_buckets)->andReturn($low_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface', $low_bucket_mock);

		$std_buckets_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface');
		$std_buckets_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 6, 10000, $expected_std_buckets, $low_bucket_return, $expected_high_buckets)->andReturn($std_buckets_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface', $std_buckets_mock);

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 10000, $price_stats, $price_buckets);

		$expected_response = [
			['low bucket stub'],
			['high bucket stub']
		];

		assertThat($response, identicalTo($expected_response));
	}

	public function testMakeSendsCorrectDataToDependenciesAndCompilesCorrectlyWhenThereAreOnlyStdBuckets()
	{	
		$price_stats = [
			'count' => 84,
			'min' => 5100,
			'max' => 50400,
			'avg' => 11600.571428571,
			'sum' => 100000,
			'sum_of_squares' => 0,
			'variance' => 0,
			'std_deviation' => 1000000
		];

		$price_buckets = [
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
			]
		];

		$expected_high_buckets = [
		];

		$high_bucket_return = [
		];

		$expected_std_buckets = [
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
			]
		];

		$std_buckets_return = [
			['std bucket stub 1'], 
			['std bucket stub 2']
		];

		$expected_low_buckets = [
		];

		$low_bucket_return = [
		];
		
		$bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price', $bucket_label_mock);

		$high_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface');
		$high_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', $expected_high_buckets, $low_bucket_return, $std_buckets_return)->andReturn($high_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface', $high_bucket_mock);
		
		$low_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface');
		$low_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 10000, $expected_low_buckets)->andReturn($low_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface', $low_bucket_mock);

		$std_buckets_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface');
		$std_buckets_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 6, 10000, $expected_std_buckets, $low_bucket_return, $expected_high_buckets)->andReturn($std_buckets_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface', $std_buckets_mock);

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 10000, $price_stats, $price_buckets);

		$expected_response = [
			['std bucket stub 1'],
			['std bucket stub 2']
		];

		assertThat($response, identicalTo($expected_response));
	}

	public function testMakeWillNotReturnOnlyOneBucket()
	{	
		$price_stats = [
			'count' => 10,
			'min' => 10000,
			'max' => 10000,
			'avg' => 10000,
			'sum' => 100000,
			'sum_of_squares' => 0,
			'variance' => 0,
			'std_deviation' => 0
		];

		$price_buckets = [
			[
				'key' => 10000,
				'doc_count' => 10
			]
		];

		$expected_high_buckets = [
		];

		$high_bucket_return = [
		];

		$expected_std_buckets = [
			[
				'key' => 10000,
				'doc_count' => 10
			]
		];

		$std_buckets_return = [
			['std bucket stub 1']
		];

		$expected_low_buckets = [
		];

		$low_bucket_return = [
		];
		
		$bucket_label_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface');
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price', $bucket_label_mock);

		$high_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface');
		$high_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', $expected_high_buckets, $low_bucket_return, $std_buckets_return)->andReturn($high_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface', $high_bucket_mock);
		
		$low_bucket_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface');
		$low_bucket_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 10000, $expected_low_buckets)->andReturn($low_bucket_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface', $low_bucket_mock);

		$std_buckets_mock = Mockery::mock('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface');
		$std_buckets_mock->shouldReceive('make')->with($bucket_label_mock, 'filter_field_name', 6, 10000, $expected_std_buckets, $low_bucket_return, $expected_high_buckets)->andReturn($std_buckets_return);
		$this->app->instance('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface', $std_buckets_mock);

		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 10000, $price_stats, $price_buckets);

		$expected_response = [
		];

		assertThat($response, identicalTo($expected_response));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenFilterFieldNameIsEmpty()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', '', 1, 10000, [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxFacetsIsEmpty()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', '', 10000, [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxFacetsIsNotAnInteger()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 'one', 10000, [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxFacetsIsZero()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 0, 10000, [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxBucketRangeIsNotAnInteger()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 'one', [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxBucketRangeIsZero()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 0, [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenMaxBucketRangeIsEmpty()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, '', [], []);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenStatsIsEmpty()
	{
		$generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator');
		$response = $generator->make('Price', 'filter_field_name', 6, 1000, [], ['foobar']);
	}
	
}