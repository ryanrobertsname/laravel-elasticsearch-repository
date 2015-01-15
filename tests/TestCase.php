<?php

class TestCase extends Orchestra\Testbench\TestCase {

	protected function getPackageProviders()
	{
	    return array('Ryanrobertsname\LaravelElasticsearchRepository\LaravelElasticsearchRepositoryServiceProvider');
	}

}