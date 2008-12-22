<?php

class AuthorFixture extends CakeTestFixture {

	var $name = 'Author';
	
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'team_id'	=> array('type' => 'integer', 'length' => 10),
		'name' => array('type' => 'string', 'length' => 100)
	);

	var $records = array(
		array(
			'id' => 1,
			'team_id' => 1,
			'name' => 'Liam Gallagher'
		),
		array(
			'id' => 2,
			'team_id' => 2,
			'name' => 'Julian Casablancas'
		),
		array(
			'id' => 3,
			'team_id' => 3,
			'name' => 'Steve Jobs'
		),
		array(
			'id' => 4,
			'team_id' => 4,
			'name' => 'Linus Torvalds'
		)
	);
}