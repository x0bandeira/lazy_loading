<?php

class PostFixture extends CakeTestFixture {

	var $name = 'Post';
	
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'author_id'	=> array('type' => 'integer', 'length' => 10),
		'title' => array('type' => 'string', 'length' => 100)
	);

	var $records = array(
		array(
			'id' => 1,
			'author_id' => 1,
			'title' => 'To be Someone'
		),
		array(
			'id' => 2,
			'author_id' => 2,
			'title' => 'Red Light'
		),
		array(
			'id' => 3,
			'author_id' => 2,
			'title' => 'Razor Blade'
		),
		array(
			'id' => 4,
			'author_id' => 3,
			'title' => 'MacBook Pro'
		)
	);
}