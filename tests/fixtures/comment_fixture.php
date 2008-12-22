<?php

class CommentFixture extends CakeTestFixture {

	var $name = 'Comment';
	
	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'author_id'	=> array('type' => 'integer', 'length' => 10),
		'post_id'	=> array('type' => 'integer', 'length' => 10),
		'body' => array('type' => 'string', 'length' => 100)
	);

	var $records = array(
		array(
			'id' => 1,
			'author_id' => 2,
			'post_id' => 1,
			'body' => 'Cool song man!'
		),
		array(
			'id' => 2,
			'author_id' => 4,
			'post_id' => 4,
			'body' => 'What about an OLP"D" initiative? One Laptop Per Developer!'
		),
		array(
			'id' => 3,
			'author_id' => 3,
			'post_id' => 3,
			'body' => 'Oh yeah babe!'
		),
		array(
			'id' => 4,
			'author_id' => 1,
			'post_id' => 3,
			'body' => '[...] give up kid, only I can make it.'
		)
	);
}