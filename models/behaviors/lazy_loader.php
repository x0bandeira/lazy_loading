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
 * @version 2.0;
 */

class LazyLoaderBehavior extends ModelBehavior {
	public $mapMethods = array('/^get((_[a-z0-9_]+)|([a-z0-9]+))/' => 'lazyLoad');
	
	function _getAssociationParams(&$Model, $association) {
		$association = Inflector::underscore($association);
		$association = preg_replace('/^get_?/', '', $association);
		$association = str_replace('_', '', $association);

		$contexts[] = array(
			'model' =>& $Model,
			'path' => array($Model->alias),
			'allowedAssociations' => array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'),
			'params' => array()
		);
		
		while (!empty($contexts)) {
			$context = array_shift($contexts);
			foreach ($context['allowedAssociations'] as $associationType) {
				$multiple = ($associationType === 'hasMany' || $associationType === 'hasAndBelongsToMany');
				$associations = $context['model']->{$associationType};
				foreach ($associations as $alias => $settings) {
					$valid = (
						(strtolower($alias) === $association && !$multiple) ||
						(Inflector::pluralize(strtolower($alias)) === $association && $multiple)
					);
					if (!$valid && $associationType === 'belongsTo') {
						$contexts[] = array(
							'model' =>& $context['model']->{$alias},
							'path' => array_merge($context['path'], array($alias)),
							'allowedAssociations' => array('belongsTo'),
							'params' => array()
						);
					} elseif ($valid) {
						$_Model =& $context['model']->{$alias};
						return $params = array(
							'type' => $associationType,
							'settings' => $settings,
							'model' =>& $_Model,
							'alias' => $_Model->alias,		# debug purposes
							'path' =>  $context['path'],
							'deep' => ($context['model'] === $Model)
						);
					}
				}
			}
		}
		return false;
	}

	function lazyLoad(&$Model, $associated, $method = null, $options = array()) {
		if (!$Model->exists()) {
			throw new Exception('Model must to be instantiated');
			return false;
		} else {
			$association = $this->_getAssociationParams($Model, $associated);
			if ($association === false) {
				throw new Exception('Model  is not associated with');
				return false;
			}
		}
		
		$type = $association['type'];
		$_Model =& $association['model'];
		$multiple = ($type === 'hasMany' || $type === 'hasAndBelongsToMany');
		$instance = (!$multiple && !empty($method));

		if ($multiple) {
			if (is_array($method)) {
				$options = $method;
				unset($method);
			}
			if (empty($method)) {
				$method = 'list';
			}
		} else {
			$method = 'first';
		}
		
		$queryKeys = array('conditions' => true, 'order' => true, 'group' => true, 'fields' => ($method !== 'list'));
		$query = array_merge_recursive(
			array_intersect_key($association['settings'], array_filter($queryKeys)),
			array('recursive' => -1, 'joins' => array()),
			$options
		);

		if ($type === 'hasAndBelongsToMany') {
			$Link =& $Model->{$association['settings']['with']};

			$foreignKey = $Link->escapeField($association['settings']['foreignKey']);
			$associationKey = $_Model->escapeField();
			$associationForeignKey = $Link->escapeField($association['settings']['associationForeignKey']);
			
			$query['conditions'][] = array($foreignKey => $Model->id);
			$query['joins'][] = array(
				'alias' => $Link->alias,
				'table' => $Link->getDataSource()->fullTableName($Link),
				'type' => 'LEFT',
				'conditions' => "{$associationForeignKey} = {$associationKey}"
			);
		} elseif ($type === 'hasMany' || $type === 'hasOne') {
			$foreignKey = $_Model->escapeField($association['settings']['foreignKey']);
			$query['conditions'][] = array($foreignKey => $Model->id);
		} elseif ($type === 'belongsTo') {
			if ($association['deep'] === false) {
				if (!$_Model->Behaviors->enabled('Linkable.Linkable')) {
					$_Model->Behaviors->attach('Linkable.Linkable');
				}				
				$path = array();
				$current =& $path;
				foreach (array_reverse($association['path']) as $link) {
					$current[$link] = array();
					$current =& $current[$link];
				}
				$query['link'] = $path;
			} else {
				$foreignKey = $Model->escapeField($association['settings']['foreignKey']);
				$associationKey = $_Model->escapeField();
				$query['joins'][] = array(
					'alias' => $Model->alias,
					'table' => $Model->getDataSource()->fullTableName($Model),
					'type' => 'LEFT',
					'conditions' => "{$associationKey} = {$foreignKey}"
				);
			}
			$query['conditions'][] = array($Model->escapeField() => $Model->id);			
		}
		
		$data = $_Model->find($method, $query);
		if (!$instance) {
			return $data;
		} elseif (empty($data)) {
			throw new Exception('Model couldn\'t be instantiated');
			return false;
		} else {
			$_Model->set($data);
			return $_Model;
		}  
	}
}
