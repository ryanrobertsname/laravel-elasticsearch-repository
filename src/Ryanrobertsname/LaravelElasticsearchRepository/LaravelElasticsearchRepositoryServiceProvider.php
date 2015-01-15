<?php namespace Ryanrobertsname\LaravelElasticsearchRepository;

use Illuminate\Support\ServiceProvider;

class LaravelElasticsearchRepositoryServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	public function boot()
	{
		$this->package('ryanrobertsname/laravel-elasticsearch-repository');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//handy bindings
		
		//if (!is_null(\Config::get('laravel-elasticsearch-repository::bindings.index_repo')))
			$this->app->bind(
				//\Config::get('laravel-elasticsearch-repository::bindings.index_repo'),
				'IndexRepo',
				'Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository'
			);

		//if (!is_null(\Config::get('laravel-elasticsearch-repository::bindings.index_migrator')))
			$this->app->bind(
				//\Config::get('laravel-elasticsearch-repository::bindings.index_migrator'),
				'IndexMigrator',
				'Ryanrobertsname\LaravelElasticsearchRepository\Helper\MigrationHelper'
			);

		$this->app->bind('Ryanrobertsname\LaravelElasticsearchRepository\ElasticsearchClient', function($app){
			$params['hosts'] = \Config::get('laravel-elasticsearch-repository::index.drivers.elasticsearch.hosts');
			return new \Elasticsearch\Client($params);
		});
		
		$this->app->bind(
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\SmartRangeGeneratorInterface',
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\ESSmartRangeGenerator'
		);
		$this->app->bind(
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessorInterface',
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\HighBucket\HighBucketProcessor'
		);
		$this->app->bind(
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessorInterface',
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\LowBucket\LowBucketProcessor'
		);
		$this->app->bind(
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessorInterface',
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Processor\StdBuckets\StdBucketsProcessor'
		);

		//Bucket labeler for prices
		$this->app->bind(
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price',
			'Ryanrobertsname\LaravelElasticsearchRepository\FacetsGenerator\SmartRange\Service\BucketLabel\Price\PriceBucketLabeler'
		);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
