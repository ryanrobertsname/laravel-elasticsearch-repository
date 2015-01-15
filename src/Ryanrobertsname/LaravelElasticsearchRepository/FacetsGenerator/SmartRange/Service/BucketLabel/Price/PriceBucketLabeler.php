<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price;

use Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\BucketLabelerInterface;

class PriceBucketLabeler implements BucketLabelerInterface
{
	public function makeBucketLabel(array $bucket)
	{
		if (!is_null($bucket['min'])):
			$min = '$'.($bucket['min'] / 100);
		else:
			$min = '$0';
		endif;

		if (!is_null($bucket['max'])):
			$separator = ' &#8211; ';
			$max = '$'.($bucket['max'] / 100);
		else:
			$separator = ' ';
			$max = '&#43;';
		endif;

		return $min.$separator.$max;
	}
}