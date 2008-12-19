<?php
/*
 * LazyLoaderBehavior
 * Enables easy access to related data through a Lazy Load¹ like interface,
 * mapping methods named as "get<AssociatedModelName>" or "get_<associated_model_name>",
 * to fetch related data and return. Can receive a string as first parameter to set the find type² to be 
 * used in the operation. If the associated model to be used in the operation is related as
 * "belongsTo" or "hasOne", a boolean TRUE can be passed as first or second param to force method to return
 * the associated model instance setted with the related record data.
 * ¹ - http://en.wikipedia.org/wiki/Lazy_loading
 * ² - http://cakebaker.42dh.com/2008/09/23/an-alternative-way-to-define-custom-find-types/
 *
 * Usage:
 * Notice that the method requires the Model to be instantiated with an exitant record id, and the
 * related model to be binded during the process.
 *
 * Project belongsTo Company
 * Project hasOne Owner
 * Project hasMany Task
 * Project hasAndBelongsToMany User
 * Projects hasMany FeaturesProblemsAndBugs
 *
 * $Project->getCompany(); // returns data of the related Company
 * $Project->getOwner(); // returns data of the related Owner
 * $Project->getTasks(); // returns related tasks list
 * $Project->getUsers(); // returns related users list
 * $Project->getTasks('all'); // returns related tasks data
 * $Project->getTasks('closed'); // returns related tasks fetched by a Task::find('closed') operation
 * $Project->getCompany(true); // returns Company model instance setted with related record data
 * $Project->getTask(); // error as Project has many Task not just one
 * $Project->get_tasks(); // works too
 * $Project->get_features_problems_and_bugs(); // works too
 *
 * Although not recommended can also be through the behavior method:
 * - notice that it would break extensibility and flexibility of overriding to customization on Model side.
 * $Project->lazyLoad('Company');
 * $Project->lazyLoad('Tasks');
 *
 * LazyLoaderBehavior. What you need, When you need, The way you want.
 * RafaelBandeira <rafaelbandeira3(at)gmail(dot)com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version 1.1;
 */

class LazyLoaderBehavior extends ModelBehavior {
	public $mapMethods = array('/^get((_[a-z0-9_]+)|([a-z0-9]+))/' => 'lazyLoad');

	public function &lazyLoad(&$Model, $association, $type = null, $options = array()) {
		if (!$Model->exists()) {
			throw new Exception('Model must be associated with an existing record');
			return false;
		}
		$association = $this->_getAssociation($Model, $association);
		if ($association === false) {
			throw new InvalidArgumentException('Model must be associated with the model intended to be loaded');
			return false;
		}

		$multiple = ($association['type'] === 'hasAndBelongsToMany' || $association['type'] === 'hasMany');
		if ($multiple) {
			if (is_array($type)) {
				$options = $type;
				unset($type);
			}
			if (empty($type)) {
				$type = 'list';
			}
		} else {
			$instance = (!$multiple && !empty($type));
			$type = 'first';
		}

		$queryKeys = array('conditions' => true, 'group' => true, 'order' => true);
		if ($type !== 'list') {
			$queryKeys['fields'] = true;
		}
		$query = array_merge(
			array_intersect_key($association['association'], $queryKeys),
			array('recursive' => -1),
			$options
		);
		if (!isset($query['conditions'])) {
			$query['conditions'] = array();
		} elseif (!is_array($query['conditions'])) {
			$query['conditions'] = array($query['conditions']);
		}

		$_Model =& $Model->{$association['alias']};
		if ($association['type'] === 'hasAndBelongsToMany') {
			$Link =& $Model->{$association['association']['with']};

			$foreignKey = $Link->escapeField($association['association']['foreignKey']);
			$associationKey = $_Model->escapeField();
			$associationForeignKey = $Link->escapeField($association['association']['associationForeignKey']);

			$query['conditions'][] = array($foreignKey => $Model->id);
			$query['joins'][] = array(
				'alias' => $Link->alias,
				'table' => $Link->getDataSource()->fullTableName($Link),
				'type' => 'LEFT',
				'conditions' => "{$associationForeignKey} = {$associationKey}"
			);
		} elseif ($association['type'] === 'hasOne' || $association['type'] === 'hasMany') {
			$foreignKey = $_Model->escapeField($association['association']['foreignKey']);
			$query['conditions'][] = array($foreignKey => $Model->id);
		} else {
			$query['conditions'][] = array($_Model->escapeField() => $Model->field($association['association']['foreignKey']));
		}

		if (!$multiple && $instance) {
			$_Model->set($_Model->find($type, $query));
			return $_Model;
		} else {
			$return = $_Model->find($type, $query);
			return $return;
		}
	}

	protected function _getAssociation(&$Model, $association) {
		if (preg_match('/^get/', $association)) {
			$association = substr($association, 3);
		}
		$associateds = $Model->getAssociated();
		if (!empty($associateds)) {
			$associated = str_replace('_', '', $association);
			$alias = Inflector::classify($association);

			foreach ($associateds as $alias => $type) {
				$lowercased = strtolower($alias);
				$valid = (
					(($type === 'belongsTo' || $type === 'hasOne') && $lowercased === $associated) ||
					(Inflector::pluralize($lowercased) === $associated)
				);
				if ($valid) {
					$association = $Model->{$type}[$alias];
					return compact('alias', 'type', 'association');
				}
			}
		}
		return false;
	}
}