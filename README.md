# Laravel Elasticsearch Repository #

Repository and services that allow quick, easy, configurable integration between [Laravel](laravel.com) application and [Elasticsearch](elasticsearch.org).

### Installation ###

Currently only private access is available:

* Add the following VCS to your composer repositories array:
```
#!json
{
	"type": "vcs",
	"url": "https://ryanrobertsname@bitbucket.org/ryanrobertsname/laravel-elasticsearch-repository.git"
}
```

* Add the package to your composer require object:
```
#!json

"ryanrobertsname/laravel-elasticsearch-repository": "dev-master"

```

* Publish the package config files using the following artisan command:

```
#!shell

php artisan config:publish ryanrobertsname/laravel-elasticsearch-repository

```

### Setup ###

As an example for this section we will have a "products" index which contains two different types, "product" and "product_owner".

#### Migrations: ####

* Create your first migration file to create the "products" index and define the fields / columns for the types "product" and "product_owner" (note that you could split this up into separate migration files if preferred):
 
```
#!shell

php artisan migrate:make create_index_products
```

* Use IndexMigrator helper class to perform tasks in the migration file:

```
#!php

//up
IndexMigrator::createIndex('products');
IndexMigrator::createIndexColumns('products', 'product', [
	'title' => 'string',
	'description' => 'string',
	'price' => 'integer',
	'category' => 'string',
	'soft_delete_status' => 'integer',
	'updated_at' => [
		'type' => 'date', 
		'format' => 'YYYY-MM-dd HH:mm:s', '
		'index' => 'not_analyzed'
	]
]);
IndexMigrator::createIndexColumns('products', 'product_owner', [
	'name' => 'string',
	'gender' => 'string',
	'favorite_color' => 'string',
	'membership_status' => 'integer',
	'updated_at' => [
		'type' => 'date', 
		'format' => 'YYYY-MM-dd HH:mm:s', '
		'index' => 'not_analyzed'
	]
], 'product');

//down
IndexMigrator::deleteIndex('products');
```
NOTE: notice that "product" was added as a third argument when creating the index columns for "product_owner", this is specifying "product" as a parent type to "product_owner". This will allow us to perform certain parent / child search operations.

NOTE: every indexable document will have an id so it does not need to be defined.

#### Configuration File ####

 * Define elasticsearch server hosts (ip:port) (one or multiple hosts):

```
#!php

	'elasticsearch' => [
			'hosts' => [
				'localhost:9200'
			] 
		]
	]
```

 * Define index models, name them whatever you'd like:

```
#!php

'index_models' => [

		'product' => [],
		'product_owner' => []

]
```
 
 * Define setup params for each index model:

```
#!php

'index_models' => [

	'product' => [
		
		'setup' => [
			'index' => 'products',
			'type' => 'product',
			'mapping' => [
				'properties' => [
					'title' => 'string',
					'description' => 'string',
					'price' => 'integer',
					'category' => 'string',
					'soft_delete_status' => 'integer',
					'updated_at' => [
						'type' => 'date', 
						'format' => 'YYYY-MM-dd HH:mm:s', '
						'index' => 'not_analyzed'
					]
				],
				'parent' => null
			]
		]
		
	],
	'product_owner' => [

		'setup' => [
			'index' => 'products',
			'type' => 'product_owner',
			'mapping' => [
				'properties' => [
					'name' => 'string',
					'gender' => 'string',
					'favorite_color' => 'string',
					'membership_status' => 'integer',
					'updated_at' => [
						'type' => 'date', 
						'format' => 'YYYY-MM-dd HH:mm:s', '
						'index' => 'not_analyzed'
					]
				],
				'parent' => 'product'
			]
		]

	]
]
```

 * Define the search operations that you would like to use for each model:

```
#!php

'index_models' => [

	'product' => [
		
		'setup' => [
			'index' => 'products',
			'type' => 'product',
			'mapping' => [
				'properties' => [
					'title' => 'string',
					'description' => 'string',
					'price' => 'integer',
					'category' => 'string',
					'soft_delete_status' => 'integer',
					'updated_at' => [
						'type' => 'date', 
						'format' => 'YYYY-MM-dd HH:mm:s', '
						'index' => 'not_analyzed'
					]
				],
				'parent' => null
			]
		],

		'search' => [

			//find products that match title or description fields to supplied keywords
			'product_keywords' => [
				'query' => [
					'type' => 'String',  //query generator that should be used
					'params' => [	//query generator params
						'append' => '~1',
						//fields to search for a query match along with their weights '^3'
						'fields' => [
							'title' => '^3',  //3 times as important compared to description
							'description' => '^1'
						],
					],
				],
			],

			//find products that have product owners who match provided polling criteria
			//ie: find products that have female product owners who like the color blue
			'product_owner_poll' => [
				'query' => [
					'type' => 'Poll', //query generator that should be used
					'params' => [  //query generator params
						'has_child_type' => 'product_owner',
						'has_child_score_type' => 'sum',
						//similar field value matches and their relevance boost values
						'field_simularity_matches' => [
							'favorite_color' => [
								'red' => [
									'orange' => .5  //if polling for red, orange will match at half relevance
								],
								'orange' => [
									'red' => .5  //if polling for orange, red will match at half relevance
								]
							]
						],
						//allows for cetain field values to have field relevance boost adjustment
						'field_value_boosts' => [
							'gender' => [
								'female' => 2  //if searching for the gender being female, make the gender field twice as relevant overall
							]
						],
						//allows polled items to have overall relevance adjusted based on one or more specific field / value combo
						'poll_type_boosts' => [
							'membership_status' => [
								1 => 100,  //if item comes back as a match, and membership_status is 1, increase this items score by a multiple of 100
							]
						],
						//fields that can be polled along with their boost values
						'fields' => [
							'gender' => 2,  //everything else equal, gender field is twice as relevant as favorite_color field
							'favorite_color' => 1						],
					]
				],
				'aggs' => [  //aggregations for matches
					'price' => [  //user defined name for agg
						'agg' => 'NumberRangeAgg', //agg module to use
						'params' => [  //agg module params
							'field' => 'price',
							'interval' => 1000,
							'max_aggs' => 4  //compression will take place if more than max exist
						]
					],
					'group' => [  //user defined name for agg
						'agg' => 'TermAgg', //agg module to use
						'params' => [  //agg module params
							'field' => 'category'
						]
					]
				],
				'filters' => [
					[
						'filter' => 'TermFilter',
						'params' => [
							'field' => 'soft_delete_status',
							'value' => 0
						],
						'combine_with_like_instances' => false  //if another term filter is supplied at run time, run them independently (ie filter AND filter)
					]
				]
			]
		]
		
	],
	'product_owner' => [

		'setup' => [
			'index' => 'products',
			'type' => 'product_owner',
			'mapping' => [
				'properties' => [
					'name' => 'string',
					'gender' => 'string',
					'favorite_color' => 'string',
					'membership_status' => 'integer',
					'updated_at' => [
						'type' => 'date', 
						'format' => 'YYYY-MM-dd HH:mm:s', '
						'index' => 'not_analyzed'
					]
				],
				'parent' => 'product'
			]
		],

		'search' => []

	]
]
```

### Usage ###

#### Using Repository Directly ####

```
#!php

<?php

$index_repo = \App::make('Ryanrobertsname\LaravelElasticsearchRepository\Repository\IndexRepository');


//index:

$columns = [
	'_id' => 1,  //optional, if '_id' is not included elasticsearch will make up it's own
	'title' => 'Super Cool Title',
	'description' => 'Even cooler description'
];
		
$index_repo->model('name_of_index_model_in_config')->index($columns);


//bulk index:

$docs = [
	[
		'_id' => 1,
		'title' => 'Super Cool Title',
		'description' => 'Even cooler description'
	],
	[
		'_id' => 2,
		'title' => 'This Title Is Ok',
		'description' => 'But the description aint so great'
	]
]

$index_repo->model('name_of_index_model_in_config')->bulkIndex($docs);


//update:

$index_repo->model('name_of_index_model_in_config')->updateIndex($columns);


//delete:

$index_repo->model('name_of_index_model_in_config')->delete($id);


//search:

$index_repo->model('name_of_index_model_in_config')
	->search(
		$search_type, 
		$query, 
		$offset, 
		$limit, 
		array $filters = [], 
		array $fields = []
	);


//"more like this" search, ie find things in the same index/type that are similar to this one

$index_repo->model(''name_of_index_model_in_config'')->mltSearch($id, $limit);

?>
```

#### Using Eloquent Model Auto Index Trait ####

This trait allows eloquent models to auto index / update / delete.  Basically, you interact with you eloquent model BAU and the data will automatically be stored / syncronized in elasticsearch.  

The database actions and index actions will be wrapped in a transaction to ensure consistency.

 * Add the auto index trait to eloquent models that should auto index:

```
#!php

<?php

use Ryanrobertsname\LaravelElasticsearchRepository\Helper\EloquentAutoIndexTrait;

class Product extends \Eloquent  {

	use EloquentAutoIndexTrait;

}

?>
```

 * Define an index_model static property, this ties the eloquent model back to an index model that is defined in your index config file:

```
#!php

<?php

use Ryanrobertsname\LaravelElasticsearchRepository\Helper\EloquentAutoIndexTrait;

class Product extends \Eloquent  {

	use EloquentAutoIndexTrait;

	public static $index_model = 'product';

}

?>
```

 * For each column defined in your index_model, a matching eloquent model column will be used.  If you have a column in your index_model that does not directly correspond to a eloquent model column, you can set up a "mutator" to define the value for indexing:


```
#!php

<?php

use Ryanrobertsname\LaravelElasticsearchRepository\Helper\EloquentAutoIndexTrait;

class Product extends \Eloquent  {

	use EloquentAutoIndexTrait;

	public static $index_model = 'product';

	//used to generate value for index column 'soft_delete_status'
	public function setSoftDeleteStatusAttribute()
	{
		if (empty($this->deleted_at))
			return 0;

		return 1;
	}

}

?>
```

#### Using Eloquent Index Search Trait ####

This trait allows you to quickly execute searches defined in your index config file.  The response will return an eloquent collection with the following three keys:

 * meta
 * results
 * facets

The results key will contain a collection of eloquent models that match your search criteria.

Search example:

```
#!php

<?php

$product = \App::make('Product'); //eloquent model

$response = $product->indexSearch(
	$search_type, //as defined in index config file
	$query, //query data required by the query generator that tied to this search type
	$offset, 
	$limit, 
	array $filters = [], 
	array $fields = []
);

//$model_collection = $response['results'];

?>

```
