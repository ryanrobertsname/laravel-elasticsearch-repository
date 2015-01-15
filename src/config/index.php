<?php
return array(

	/**
	*
	* Index Driver
	*
	**/
	'driver' => 'elasticsearch',

	/**
	*
	* Driver Settings
	*
	**/
	'drivers' => [
		'elasticsearch' => [
			'hosts' => [
				'localhost:9200'
			] 
		]
	],

	/**
	*
	* Index Models
	*
	**/
	'index_models' => [

		'product' => [  //name whatever you want just dont duplicate

			//for migrations and validation of available fields
			'setup' => [
				//index name (specified here so different environments can use specifc names)
				'index' => 'products',

				//type name (specified here so different environments can use specifc names)
				'type' => 'product',

				//for mapping
				'mapping' => [
					'properties' => [
						'soft_delete_status' => 'integer',
						'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],
						'user_id' => 'integer',
						'title' => 'string',
						'descriptions' => 'string',
						'features' => 'string',
						'binding' => 'string',
						'brand' => 'string',
						'manufacturer' => 'string',
						'model' => 'string',
						'group' => [
							'type' => 'string',
							'index' => 'not_analyzed'
						],
						'size' => 'string',
						'clothing_size' => 'string',
						'min_price' => 'integer',
						'max_price' => 'integer',
						'keyword_profile' => 'string',
						'category_keywords' => 'string'
					],
					'parent' => null
				]
			],

			//for searching operations
			'search' => [
				//search type : product search
				'product_search' => [
					'query' => [
						'type' => 'String',  //query generator that should be used
						'params' => [	//query generator params
							'append' => '~1',
							//fields to search for a query match along with their weights '^3'
							'fields' => [
								'title' => '^5',
								'descriptions' => '^3',
								'features' => '^3',
								'binding' => '^1',
								'brand' => '^2',
								'manufacturer' => '^2',
								'model' => '^2',
								'group' => '^1',
								'size' => '^1',
								'clothing_size' => '^1',
								'keyword_profile' => '^2',
								'category_keywords' => '^1'
							],
						],
					],
					'aggs' => [
						'price' => [  //user defined name for agg
							'agg' => 'NumberRangeAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'min_price',
								'interval' => 1000,
								'max_aggs' => 4  //compression will take place if more than max exist
							]
						],
						'group' => [  //user defined name for agg
							'agg' => 'TermAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'group'
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
							'combine_with_like_instances' => false
						]
					]
				],
				//search type : filter_products
				'filter_products' => [
					'query' => [
						'type' => null,  //query generator that should be used
						'params' => [	//query generator params
							
						],
					],
					'aggs' => [
						
					],
					'filters' => [

					]
				],
				//search type : owner poll
				'owner_poll' => [
					'query' => [
						'type' => 'Poll', //query generator that should be used
						'params' => [  //query generator params
							'has_child_index_model' => 'product_owner',
							'has_child_score_type' => 'sum',
							//similar field value matches and their relevance boost values
							'field_simularity_matches' => [
								'location_ids' => [
									2 => [
										3 => .5
									]
								]
							],
							//allows for cetain field values to have field relevance boost adjustment
							'field_value_boosts' => [
								'relation_id' => [
									4 => 2
								]
							],
							//allows polled items to have overall relevance adjusted based on one or more specific field / value combo
							'poll_type_boosts' => [
								'location_ids' => [
									2 => 500,  //example, view x as relevant overall
									1 => 800
								]
							],
							//fields that can be polled along with their boost values
							'fields' => [
								'gender_ids' => 1,
								'location_ids' => 1,
							],
						]
					],
					'aggs' => [
						'price' => [  //user defined name for agg
							'agg' => 'NumberRangeAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'min_price',
								'interval' => 1000,
								'max_aggs' => 4  //compression will take place if more than max exist
							]
						],
						'group' => [  //user defined name for agg
							'agg' => 'TermAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'group'
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
							'combine_with_like_instances' => false
						]
					]
				],
				//search type : owner count
				'owner_count' => [
					'query' => [
						'type' => 'ChildrenCount', //query generator that should be used
						'params' => [  //query generator params
							'has_child_index_model' => 'product_owner',
							'has_child_score_type' => 'sum'
						]
					],
					'aggs' => [
						'price' => [  //user defined name for agg
							'agg' => 'NumberRangeAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'min_price',
								'interval' => 1000,
								'max_aggs' => 6  //compression will take place if more than max exist
							]
						],
						'group' => [  //user defined name for agg
							'agg' => 'TermAgg', //agg module to use
							'params' => [  //agg module params
								'field' => 'group'
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
							'combine_with_like_instances' => false
						]
					]
				],
				//search type : find others most like this
				'mlt_search' => [
					'query' => [
						'type' => 'Mlt', //query generator that should be used
						'params' => [  //query generator params
							'min_term_freq' => 1,
	      					'min_doc_freq' => 1
						]
					],
					'aggs' => [

					],
					'filters' => [
						[
							'filter' => 'TermFilter',
							'params' => [
								'field' => 'soft_delete_status',
								'value' => 0
							],
							'combine_with_like_instances' => false
						]
					]
				],
				'owner_hits' => [
					'query' => [
						'type' => 'FilterByChildren', //query generator that should be used
						'params' => [  //query generator params
							'has_child_index_model' => 'product_owner',
							//fields that can be filtered, null boost values since we are filtering
							'fields' => [
								'gender_ids' => null,		
							],
						],
					],
					'aggs' => [

					],
					'filters' => [
						[
							'filter' => 'TermFilter',
							'params' => [
								'field' => 'soft_delete_status',
								'value' => 0
							],
							'combine_with_like_instances' => false
						]
					]
				]
			]

		],

		'product_owners' => [  //name whatever you want just dont duplicate

			//for migrations and validation of available fields
			'setup' => [
				//index name (specified here so different environments can use specifc names)
				'index' => 'products',

				//type name (specified here so different environments can use specifc names)
				'type' => 'owner',

				//for mapping
				'mapping' => [
					'properties' => [
						'gender_ids' => 'integer',
						'location_ids' => 'integer',
						'datetime' => ['type' => 'date', 'format' => 'YYYY-MM-dd HH:mm:s', 'index' => 'not_analyzed'],					],
					//parent type
					'parent' => 'product'
				]
			]

		]
	]
);