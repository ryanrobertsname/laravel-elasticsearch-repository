<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\AbstractFacetGenerator;

/**
* ESTermsGenerator
*/
class ESTermGenerator extends AbstractFacetGenerator implements TermGeneratorInterface
{
	public function make($filter_field_name, array $buckets)
	{
		if (empty($filter_field_name) || empty($buckets))
			throw new \InvalidArgumentException();

		foreach ($buckets as $bucket)
			$facets[] = $this->generateFacet($filter_field_name, $bucket);

		return $this->cleanFacet($facets);
	}

	protected function generateFacet($filter_field_name, $bucket)
	{
		return [
			'label' => $bucket['key'],
			'count' => $bucket['doc_count'],
			'filter' => [
				'filter' => 'TermFilter',
				'params' => [
					'field' => $filter_field_name,
					'value' => $bucket['key']
				]
			]
		];
	}
}