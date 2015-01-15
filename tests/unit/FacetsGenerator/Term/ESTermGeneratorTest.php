<?php 

/**
* Class
*/
class ESTermGeneratorTest extends TestCase
{
	protected $high_buckets = [];

	public function setUp()
	{
		parent::setUp();
	}

	public function testMakeReturnsCorrectFacet()
	{
		$term_facet_generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator');

		$filter_field_name_stub = 'testfield';

		$buckets_stub = [
			[
				'key' => 'value1',
				'doc_count' => 3
			],
			[
				'key' => 'value2',
				'doc_count' => 6
			]
		];

		$expected_response = [
			[
				'label' => 'value1',
				'count' => 3,
				'filter' => [
					'filter' => 'TermFilter',
					'params' => [
						'field' => 'testfield',
						'value' => 'value1'
					]
				]
			],
			[
				'label' => 'value2',
				'count' => 6,
				'filter' => [
					'filter' => 'TermFilter',
					'params' => [
						'field' => 'testfield',
						'value' => 'value2'
					]
				]
			]
		];

		$response = $term_facet_generator->make($filter_field_name_stub, $buckets_stub);

		assertThat($response, identicalTo($expected_response));
	}

	public function testMakeReturnsNoFacetDivisionsWhenIndexReturnsOnlyOneDivision()
	{
		$term_facet_generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator');

		$filter_field_name_stub = 'testfield';

		$buckets_stub = [
			[
				'key' => 'value1',
				'doc_count' => 3
			]
		];

		$expected_response = [

				
		];

		$response = $term_facet_generator->make($filter_field_name_stub, $buckets_stub);

		assertThat($response, identicalTo($expected_response));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenFilterFieldNameIsEmpty()
	{
		$term_facet_generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator');

		$filter_field_name_stub = '';

		$buckets_stub = [
			[
				'key' => 'value1',
				'doc_count' => 3
			],
			[
				'key' => 'value2',
				'doc_count' => 6
			]
		];

		$response = $term_facet_generator->make($filter_field_name_stub, $buckets_stub);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testMakeThrowsInvalidArgExceptionWhenBucketsAreEmpty()
	{
		$term_facet_generator = $this->app->make('Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term\ESTermGenerator');

		$filter_field_name_stub = 'fieldnametest';

		$buckets_stub = [
		];

		$response = $term_facet_generator->make($filter_field_name_stub, $buckets_stub);
	}
}