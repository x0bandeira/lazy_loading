<?php
/*
 * Unit test for LazyLoaderBehavior v1.0
 *
 * LazyLoaderBehavior. What you need, When you need, The way you want.
 * RafaelBandeira <rafaelbandeira3(at)gmail(dot)com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 */

App::import('Behavior', 'LazyLoader');
App::import('Model', 'App');
include CAKE_TESTS . 'cases' . DS . 'libs' . DS . 'model' . DS . 'models.php';

class LazyLoaderBehaviorTest extends CakeTestCase {

	var $fixtures = array(
		'core.portfolio', 'core.item', 'core.items_portfolio',
		'core.syfile', 'core.image', 'core.message',
		'core.thread', 'core.bid', 'core.project'
	);

	function start() {
		parent::start();
		$this->Portfolio = ClassRegistry::init('Portfolio');
		$this->Item = ClassRegistry::init('Item');
		$this->Syfile = ClassRegistry::init('Syfile');
		$this->Image = ClassRegistry::init('Image');
		$this->Message = ClassRegistry::init('Message');
		$this->Thread = ClassRegistry::init('Thread');

		$this->Portfolio->Behaviors->attach('LazyLoader');
		$this->Item->Behaviors->attach('LazyLoader');
		$this->Syfile->Behaviors->attach('LazyLoader');
		$this->Image->Behaviors->attach('LazyLoader');
		$this->Message->Behaviors->attach('LazyLoader');
		$this->Thread->Behaviors->attach('LazyLoader');

		$this->Image->bind('Syfile', array('hasOne'));
	}

	function testBelongsTo() {
		$this->Syfile->id = 1;
		$result = $this->Syfile->getImage();
		$expected = array('Image' => array('id' => 1, 'name' => 'Image 1'));
		$this->assertEqual($result, $expected);
	}

	function testBelongsToLoadInstance() {
		$this->Syfile->id = 1;
		$result = $this->Syfile->getImage(true);
		$this->assertIsA($result, 'Image');
	}

	function testHasOne() {
		$this->Image->id = 1;
		$result = $this->Image->getSyfile();
		$expected = array('Syfile' => array('id' => 1, 'image_id' => 1, 'name' => 'Syfile 1', 'item_count' => null));
		$this->assertEqual($result, $expected);
	}

	function testHasOneLoadInstance() {
		$this->Image->id = 1;
		$result = $this->Image->getSyfile(true);
		$this->assertIsA($result, 'Syfile');
	}

	function testHasAndBelongsToMany() {
		$this->Portfolio->id = 1;
		$result = $this->Portfolio->getItems();
		$expected = array(1 => 'Item 1', 3 => 'Item 3', 4 => 'Item 4', 5 => 'Item 5');
		$this->assertEqual($result, $expected);
	}

	function testHasMany() {
		$this->Thread->id = 1;
		$result = $this->Thread->getMessages();
		$expected = array(1 => 'Thread 1, Message 1');
		$this->assertEqual($result, $expected);
	}

	function testAlternativeFindType() {
		$this->Portfolio->id = 1;
		$result = $this->Portfolio->getItems('all');
		$expected = array(
			array('Item' => array('id' => 1, 'syfile_id' => 1, 'published' => 0, 'name' => 'Item 1')),
			array('Item' => array('id' => 3, 'syfile_id' => 3, 'published' => 0, 'name' => 'Item 3')),
			array('Item' => array('id' => 4, 'syfile_id' => 4, 'published' => 0, 'name' => 'Item 4')),
			array('Item' => array('id' => 5, 'syfile_id' => 5, 'published' => 0, 'name' => 'Item 5'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->Portfolio->getItems('count');
		$expected = 4;
		$this->assertEqual($result, $expected);
	}

	function testUnderscoredStyle() {
		$this->Portfolio->id = 1;
		$result = $this->Portfolio->get_items();
		$expected = array(1 => 'Item 1', 3 => 'Item 3', 4 => 'Item 4', 5 => 'Item 5');
		$this->assertEqual($result, $expected);

		$this->Image->id = 1;
		$result = $this->Image->get_syfile();
		$expected = array('Syfile' => array('id' => 1, 'image_id' => 1, 'name' => 'Syfile 1', 'item_count' => null));
		$this->assertEqual($result, $expected);
	}

	function testUninstantiatedModel() {
		$this->expectException();
		$this->Image->id = false;
		$result = $this->Image->getSyfile();
		$this->assertFalse($result);
	}

	function testUnexistantAssociation() {
		$this->expectException();
		$this->Image->id = 1;
		$result = $this->Image->getACherryFromTheTopOfTheCakeForMePlease();
		$this->assertFalse($result);
	}

	function testOnTheFlyAssociations() {
		$this->Message->bind('Thread');
		$this->Message->id = 1;
		$result = $this->Message->getThread();
		$expected = array('Thread' => array('id' => 1, 'project_id' => 1, 'name' => 'Project 1, Thread 1'));
		$this->assertEqual($result, $expected);

		$result = $this->Message->getThread(true);
		$this->assertIsA($result, 'Thread');		
	}

	function testComposedNameAssociation() {
		$this->Image->unbindModel(array('hasOne' => array('Syfile')));
		$this->Image->bind('SystemFile', array('hasOne', 'className' => 'Syfile'), false);

		$this->Image->id = 1;
		$result = $this->Image->getSystemFile();
		$expected = array('SystemFile' => array('id' => 1, 'image_id' => 1, 'name' => 'Syfile 1', 'item_count' => null));
		$this->assertEqual($result, $expected);

		$this->Portfolio->unbindModel(array('hasAndBelongsToMany' => array('Item')));
		$this->Portfolio->bind('RelatedItem', array('hasAndBelongsToMany', 'className' => 'Item'), false);

		$this->Portfolio->id = 1;
		$result = $this->Portfolio->getRelatedItems();
		$expected = array(1 => 'Item 1', 3 => 'Item 3', 4 => 'Item 4', 5 => 'Item 5');
		$this->assertEqual($result, $expected);
	}

	function testUnderscoredStyleComposedNameAssociation() {
		$this->Image->unbindModel(array('hasOne' => array('Syfile')));
		$this->Image->bind('SystemFile', array('hasOne', 'className' => 'Syfile'), false);

		$this->Image->id = 1;
		$result = $this->Image->get_system_file();
		$expected = array('SystemFile' => array('id' => 1, 'image_id' => 1, 'name' => 'Syfile 1', 'item_count' => null));
		$this->assertEqual($result, $expected);

		$this->Portfolio->unbindModel(array('hasAndBelongsToMany' => array('Item')));
		$this->Portfolio->bind('RelatedItem', array('hasAndBelongsToMany', 'className' => 'Item'), false);

		$this->Portfolio->id = 1;
		$result = $this->Portfolio->get_related_items();
		$expected = array(1 => 'Item 1', 3 => 'Item 3', 4 => 'Item 4', 5 => 'Item 5');
		$this->assertEqual($result, $expected);
	}

	function testErroneousRegexpMatch() {
		$this->Syfile->recursive = -1;
		$result = $this->Syfile->findById(1);
		$expected = array('Syfile' => array('id' => 1, 'image_id' => 1, 'name' => 'Syfile 1', 'item_count' => null));
		$this->assertEqual($result, $expected);

		$this->expectError();
		$this->Syfile->someMethodThatWillNeverBeHandledAsItIsNotOnlyNotImplementedInTheModelAsItIsNotImplementedInAnyOfItsBehaviors();
	}
}