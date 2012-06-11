<?php
class DAO_TwitterMessage extends C4_ORMHelper {
	const ID = 'id';
	const TWITTER_ID = 'twitter_id';
	const TWITTER_USER_ID = 'twitter_user_id';
	const USER_NAME = 'user_name';
	const USER_SCREEN_NAME = 'user_screen_name';
	const USER_FOLLOWERS_COUNT = 'user_followers_count';
	const USER_PROFILE_IMAGE_URL = 'user_profile_image_url';
	const CREATED_DATE = 'created_date';
	const IS_CLOSED = 'is_closed';
	const CONTENT = 'content';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO twitter_message () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'twitter_message', $fields);
		
		// Log the context update
	    //DevblocksPlatform::markContextChanged('example.context', $ids);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('twitter_message', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_TwitterMessage[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, twitter_id, twitter_user_id, user_name, user_screen_name, user_followers_count, user_profile_image_url, created_date, is_closed, content ".
			"FROM twitter_message ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TwitterMessage	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TwitterMessage[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_TwitterMessage();
			$object->id = $row['id'];
			$object->twitter_id = $row['twitter_id'];
			$object->twitter_user_id = $row['twitter_user_id'];
			$object->user_name = $row['user_name'];
			$object->user_screen_name = $row['user_screen_name'];
			$object->user_followers_count = $row['user_followers_count'];
			$object->user_profile_image_url = $row['user_profile_image_url'];
			$object->created_date = $row['created_date'];
			$object->is_closed = $row['is_closed'];
			$object->content = $row['content'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM twitter_message WHERE id IN (%s)", $ids_list));
		
		// Fire event
		/*
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => 'cerberusweb.contexts.',
                	'context_ids' => $ids
                )
            )
	    );
	    */
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_TwitterMessage::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"twitter_message.id as %s, ".
			"twitter_message.twitter_id as %s, ".
			"twitter_message.twitter_user_id as %s, ".
			"twitter_message.user_name as %s, ".
			"twitter_message.user_screen_name as %s, ".
			"twitter_message.user_followers_count as %s, ".
			"twitter_message.user_profile_image_url as %s, ".
			"twitter_message.created_date as %s, ".
			"twitter_message.is_closed as %s, ".
			"twitter_message.content as %s ",
				SearchFields_TwitterMessage::ID,
				SearchFields_TwitterMessage::TWITTER_ID,
				SearchFields_TwitterMessage::TWITTER_USER_ID,
				SearchFields_TwitterMessage::USER_NAME,
				SearchFields_TwitterMessage::USER_SCREEN_NAME,
				SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT,
				SearchFields_TwitterMessage::USER_PROFILE_IMAGE_URL,
				SearchFields_TwitterMessage::CREATED_DATE,
				SearchFields_TwitterMessage::IS_CLOSED,
				SearchFields_TwitterMessage::CONTENT
			);
			
		$join_sql = "FROM twitter_message ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'twitter_message.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		array_walk_recursive(
			$params,
			array('DAO_TwitterMessage', '_translateVirtualParameters'),
			array(
				'join_sql' => &$join_sql,
				'where_sql' => &$where_sql,
				'has_multiple_values' => &$has_multiple_values
			)
		);
	
		return array(
			'primary_table' => 'twitter_message',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
	}
	
	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		//$from_context = CerberusContexts::CONTEXT_EXAMPLE;
		//$from_index = 'example.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			/*
			case SearchFields_EXAMPLE::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			*/
		}
	}
	
    /**
     * Enter description here...
     *
     * @param array $columns
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY twitter_message.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_TwitterMessage::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT twitter_message.id) " : "SELECT COUNT(twitter_message.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_TwitterMessage implements IDevblocksSearchFields {
	const ID = 't_id';
	const TWITTER_ID = 't_twitter_id';
	const TWITTER_USER_ID = 't_twitter_user_id';
	const USER_NAME = 't_user_name';
	const USER_SCREEN_NAME = 't_user_screen_name';
	const USER_FOLLOWERS_COUNT = 't_user_followers_count';
	const USER_PROFILE_IMAGE_URL = 't_user_profile_image_url';
	const CREATED_DATE = 't_created_date';
	const IS_CLOSED = 't_is_closed';
	const CONTENT = 't_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'twitter_message', 'id', $translate->_('common.id'), null),
			self::TWITTER_ID => new DevblocksSearchField(self::TWITTER_ID, 'twitter_message', 'twitter_id', $translate->_('dao.twitter_message.twitter_id'), null),
			self::TWITTER_USER_ID => new DevblocksSearchField(self::TWITTER_USER_ID, 'twitter_message', 'twitter_user_id', $translate->_('dao.twitter_message.twitter_user_id'), null),
			self::USER_NAME => new DevblocksSearchField(self::USER_NAME, 'twitter_message', 'user_name', $translate->_('dao.twitter_message.user_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::USER_SCREEN_NAME => new DevblocksSearchField(self::USER_SCREEN_NAME, 'twitter_message', 'user_screen_name', $translate->_('dao.twitter_message.user_screen_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::USER_FOLLOWERS_COUNT => new DevblocksSearchField(self::USER_FOLLOWERS_COUNT, 'twitter_message', 'user_followers_count', $translate->_('dao.twitter_message.user_followers_count'), Model_CustomField::TYPE_NUMBER),
			self::USER_PROFILE_IMAGE_URL => new DevblocksSearchField(self::USER_PROFILE_IMAGE_URL, 'twitter_message', 'user_profile_image_url', $translate->_('dao.twitter_message.user_profile_image_url'), null),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'twitter_message', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'twitter_message', 'is_closed', $translate->_('dao.twitter_message.is_closed'), Model_CustomField::TYPE_CHECKBOX),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'twitter_message', 'content', $translate->_('common.content'), Model_CustomField::TYPE_MULTI_LINE),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getByContext(CerberusContexts::XXX);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name,$field->type);
		//}
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_TwitterMessage {
	public $id;
	public $twitter_id;
	public $twitter_user_id;
	public $user_name;
	public $user_screen_name;
	public $user_followers_count;
	public $user_profile_image_url;
	public $created_date;
	public $is_closed;
	public $content;
};

class View_TwitterMessage extends C4_AbstractView {
	const DEFAULT_ID = 'twittermessage';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] Name the worklist view
		$this->name = $translate->_('TwitterMessage');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TwitterMessage::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_TwitterMessage::USER_NAME,
			SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT,
			SearchFields_TwitterMessage::CREATED_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TwitterMessage::ID,
			SearchFields_TwitterMessage::TWITTER_ID,
			SearchFields_TwitterMessage::TWITTER_USER_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_TwitterMessage::ID,
			SearchFields_TwitterMessage::TWITTER_ID,
			SearchFields_TwitterMessage::TWITTER_USER_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TwitterMessage::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TwitterMessage', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:wgm.twitter::tweet/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_TwitterMessage::TWITTER_ID:
			case SearchFields_TwitterMessage::TWITTER_USER_ID:
			case SearchFields_TwitterMessage::USER_NAME:
			case SearchFields_TwitterMessage::USER_SCREEN_NAME:
			case SearchFields_TwitterMessage::USER_PROFILE_IMAGE_URL:
			case SearchFields_TwitterMessage::CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_TwitterMessage::ID:
			case SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case SearchFields_TwitterMessage::IS_CLOSED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_TwitterMessage::CREATED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			/*
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
			*/
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_TwitterMessage::IS_CLOSED:
				parent::_renderCriteriaParamBoolean($param);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		$translate = DevblocksPlatform::getTranslationService();
		
		switch($key) {
		}
	}

	function getFields() {
		return SearchFields_TwitterMessage::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TwitterMessage::TWITTER_ID:
			case SearchFields_TwitterMessage::TWITTER_USER_ID:
			case SearchFields_TwitterMessage::USER_NAME:
			case SearchFields_TwitterMessage::USER_SCREEN_NAME:
			case SearchFields_TwitterMessage::USER_PROFILE_IMAGE_URL:
			case SearchFields_TwitterMessage::CONTENT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_TwitterMessage::ID:
			case SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_TwitterMessage::CREATED_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_TwitterMessage::IS_CLOSED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			/*
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
			*/
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
	
		$change_fields = array();
		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_TwitterMessage::EXAMPLE] = 'some value';
					break;
				/*
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
				*/
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_TwitterMessage::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_TwitterMessage::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_TwitterMessage::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_TwitterMessage::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_TwitterMessage extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.twitter.message';
	
	function getRandom() {
		//return DAO_TwitterMessage::random();
	}
	
	function getMeta($context_id) {
		$tweet = DAO_TwitterMessage::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		//$friendly = DevblocksPlatform::strToPermalink($example->name);
		
		return array(
			'id' => $tweet->id,
			'name' => $tweet->content,
			'permalink' => $url_writer->writeNoProxy(sprintf("c=profiles&=type=twitter_message&id=%d",$context_id), true),
		);
	}
	
	function getContext($tweet, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Twitter Message:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_TwitterMessage::ID);

		// Polymorph
		if(is_numeric($tweet)) {
			$tweet = DAO_TwitterMessage::get($tweet);
		} elseif($tweet instanceof Model_TwitterMessage) {
			// It's what we want already.
		} else {
			$tweet = null;
		}
		
		// Token labels
		$token_labels = array(
			'content' => $prefix.$translate->_('common.content'),
			'created|date' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			//'name' => $prefix.$translate->_('common.name'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_TwitterMessage::ID;
		
		if($tweet) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $tweet->content;
			$token_values['created'] = $tweet->created_date;
			$token_values['id'] = $tweet->id;
			$token_values['content'] = $tweet->content;
			
			// URL
			//$url_writer = DevblocksPlatform::getUrlService();
			//$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=example.object&id=%d-%s",$tweet->id, DevblocksPlatform::strToPermalink($tweet->name)), true);
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_TwitterMessage::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}	
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->view_columns = array(
			SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT,
			SearchFields_TwitterMessage::USER_SCREEN_NAME,
			SearchFields_TwitterMessage::CREATED_DATE,
		);
		$view->addParams(array(
		), true);
		$view->renderSortBy = SearchFields_TwitterMessage::CREATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		$view->renderFilters = false;
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_TwitterMessage::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_TwitterMessage::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};