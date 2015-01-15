<?php namespace Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\Term;

interface TermGeneratorInterface
{
	public function make($filter_field_name, array $buckets);			
}