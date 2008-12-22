<?php

class TeamFixture extends CakeTestFixture {

	var $name = 'Team';
	
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'industry_id'	=> array('type' => 'integer', 'length' => 10),
		'name' => array('type' => 'string', 'length' => 100)
	);

	var $records = array(
		array(
			'id' => 1,
			'industry_id' => 1,
			'name' => 'Oasis'
		),
		array(
			'id' => 2,
			'industry_id' => 1,
			'name' => 'The Strokes'
		),
		array(
			'id' => 3,
			'industry_id' => 2,
			'name' => 'Apple'
		),
		array(
			'id' => 4,
			'industry_id' => 2,
			'name' => 'Linux'
		)
	);
}