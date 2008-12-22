<?php

class IndustryFixture extends CakeTestFixture {

	var $name = 'Industry';
	
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'name' => array('type' => 'string', 'length' => 100)
	);

	var $records = array(
		array(
			'id' => 1,
			'name' => 'Music'
		),
		array(
			'id' => 2,
			'name' => 'Software'
		)
	);
}