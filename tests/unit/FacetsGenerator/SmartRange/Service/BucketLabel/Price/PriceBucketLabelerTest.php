<?php 

/**
* Class
*/
class PriceBucketLabelerTest extends TestCase
{
	public function setUp()
	{
		parent::setUp();

		$this->labeler = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price\PriceBucketLabeler');
	}

	public function testMakeBucketLabelReturnsExpectedResponseWhenMinIsNull()
	{
		$expected_response = '$0 &#8211; $100';
		$bucket = [
			'min' => null,
			'max' => 10000
		];

		$response = $this->labeler->makeBucketLabel($bucket);

		assertThat($response, identicalTo($expected_response));
	}
	
	public function testMakeBucketLabelReturnsExpectedResponse()
	{
		$expected_response = '$10 &#8211; $100';
		$bucket = [
			'min' => 1000,
			'max' => 10000
		];

		$response = $this->labeler->makeBucketLabel($bucket);

		assertThat($response, identicalTo($expected_response));
	}
	
	public function testMakeBucketLabelReturnsExpectedResponseWhenMaxIsNull()
	{
		$expected_response = '$10 &#43;';
		$bucket = [
			'min' => 1000,
			'max' => null
		];

		$response = $this->labeler->makeBucketLabel($bucket);

		assertThat($response, identicalTo($expected_response));
	}
}