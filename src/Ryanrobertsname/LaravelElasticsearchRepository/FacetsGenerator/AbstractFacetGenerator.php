<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator;

/**
* class
*/
abstract class AbstractFacetGenerator
{
	/**
	 * do not return a single facet division
	 * @param  array $facets
	 * @return array
	 */
	protected function cleanFacet($facets)		
	{
		if (count($facets) > 1)
			return $facets;

		return [];
	}
}