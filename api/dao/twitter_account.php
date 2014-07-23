<?php
class DAO_TwitterAccount extends Cerb_ORMHelper {
	const _CACHE_ALL = 'twitter_accounts_all';
	
	const ID = 'id';
	const TWITTER_ID = 'twitter_id';
	const SCREEN_NAME = 'screen_name';
	const OAUTH_TOKEN = 'oauth_token';
	const OAUTH_TOKEN_SECRET = 'oauth_token_secret';
	const LAST_SYNCED_AT = 'last_synced_at';
	const LAST_SYNCED_MSGID = 'last_synced_msgid';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO twitter_account () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges('cerberusweb.contexts.twitter.account', $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'twitter_account', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.twitter_account.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged('cerberusweb.contexts.twitter.account', $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		if($nocache || null === ($accounts = $cache->load(self::_CACHE_ALL))) {
			$accounts = self::getWhere();
			$cache->save($accounts, self::_CACHE_ALL);
		}
		
		return $accounts;
	}
	
	static function getByTwitterId($twitter_id) {
		$accounts = self::getAll();
		
		foreach($accounts as $account) { /* @var $account Model_TwitterAccount */
			if($account->twitter_id == $twitter_id)
				return $account;
		}
		
		return NULL;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_TwitterAccount[]
	 */
	static function getWhere($where=null, $sortBy='screen_name', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, twitter_id, screen_name, oauth_token, oauth_token_secret, last_synced_at, last_synced_msgid ".
			"FROM twitter_account ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TwitterAccount	 */
	static function get($id) {
		$accounts = self::getAll();
		
		if(isset($accounts[$id]))
			return $accounts[$id];
		
		return NULL;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_TwitterAccount[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TwitterAccount();
			$object->id = $row['id'];
			$object->twitter_id = $row['twitter_id'];
			$object->screen_name = $row['screen_name'];
			$object->oauth_token = $row['oauth_token'];
			$object->oauth_token_secret = $row['oauth_token_secret'];
			$object->last_synced_at = intval($row['last_synced_at']);
			$object->last_synced_msgid = $row['last_synced_msgid'] ?: '0';
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM twitter_account WHERE id IN (%s)", $ids_list));
		
		DAO_TwitterMessage::deleteByAccounts($ids);
		
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
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_TwitterAccount::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"twitter_account.id as %s, ".
			"twitter_account.twitter_id as %s, ".
			"twitter_account.screen_name as %s, ".
			"twitter_account.oauth_token as %s, ".
			"twitter_account.oauth_token_secret as %s, ".
			"twitter_account.last_synced_at as %s, ".
			"twitter_account.last_synced_msgid as %s ",
				SearchFields_TwitterAccount::ID,
				SearchFields_TwitterAccount::TWITTER_ID,
				SearchFields_TwitterAccount::SCREEN_NAME,
				SearchFields_TwitterAccount::OAUTH_TOKEN,
				SearchFields_TwitterAccount::OAUTH_TOKEN_SECRET,
				SearchFields_TwitterAccount::LAST_SYNCED_AT,
				SearchFields_TwitterAccount::LAST_SYNCED_MSGID
			);
			
		$join_sql = "FROM twitter_account ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'twitter_account.id',
		//	$select_sql,
		//	$join_sql
		//);
		$has_multiple_values = false; // [TODO] Temporary when custom fields disabled
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
	
		// Virtuals
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_TwitterAccount', '_translateVirtualParameters'),
			$args
		);
		
		return array(
			'primary_table' => 'twitter_account',
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
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
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
			($has_multiple_values ? 'GROUP BY twitter_account.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_TwitterAccount::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT twitter_account.id) " : "SELECT COUNT(twitter_account.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOne($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
	static public function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}

};

class SearchFields_TwitterAccount implements IDevblocksSearchFields {
	const ID = 't_id';
	const TWITTER_ID = 't_twitter_id';
	const SCREEN_NAME = 't_screen_name';
	const OAUTH_TOKEN = 't_oauth_token';
	const OAUTH_TOKEN_SECRET = 't_oauth_token_secret';
	const LAST_SYNCED_AT = 't_last_synced_at';
	const LAST_SYNCED_MSGID = 't_last_synced_msgid';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'twitter_account', 'id', $translate->_('dao.twitter_account.id'), null),
			self::TWITTER_ID => new DevblocksSearchField(self::ID, 'twitter_account', 'twitter_id', null),
			self::SCREEN_NAME => new DevblocksSearchField(self::SCREEN_NAME, 'twitter_account', 'screen_name', $translate->_('dao.twitter_account.screen_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::OAUTH_TOKEN => new DevblocksSearchField(self::OAUTH_TOKEN, 'twitter_account', 'oauth_token', null, null),
			self::OAUTH_TOKEN_SECRET => new DevblocksSearchField(self::OAUTH_TOKEN_SECRET, 'twitter_account', 'oauth_token_secret', null, null),
			self::LAST_SYNCED_AT => new DevblocksSearchField(self::LAST_SYNCED_AT, 'twitter_account', 'last_synced_at', $translate->_('dao.twitter_account.last_synced_at'), Model_CustomField::TYPE_DATE),
			self::LAST_SYNCED_MSGID => new DevblocksSearchField(self::LAST_SYNCED_MSGID, 'twitter_account', 'last_synced_msgid', $translate->_('dao.twitter_account.last_synced_msgid'), null),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			'cerberusweb.contexts.twitter.account',
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_TwitterAccount {
	public $id;
	public $twitter_id;
	public $screen_name;
	public $oauth_token;
	public $oauth_token_secret;
	public $last_synced_at;
	public $last_synced_msgid;
};

class View_TwitterAccount extends C4_AbstractView {
	const DEFAULT_ID = 'twitter_account';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Twitter Accounts');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TwitterAccount::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_TwitterAccount::SCREEN_NAME,
			SearchFields_TwitterAccount::LAST_SYNCED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TwitterAccount::ID,
			SearchFields_TwitterAccount::LAST_SYNCED_MSGID,
			SearchFields_TwitterAccount::OAUTH_TOKEN,
			SearchFields_TwitterAccount::OAUTH_TOKEN_SECRET,
			SearchFields_TwitterAccount::TWITTER_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_TwitterAccount::ID,
			SearchFields_TwitterAccount::LAST_SYNCED_MSGID,
			SearchFields_TwitterAccount::OAUTH_TOKEN,
			SearchFields_TwitterAccount::OAUTH_TOKEN_SECRET,
			SearchFields_TwitterAccount::TWITTER_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_TwitterAccount::search(
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
		return $this->_doGetDataSample('DAO_TwitterAccount', $size);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::XXX);
		//$tpl->assign('custom_fields', $custom_fields);

		$tpl->display('devblocks:wgm.twitter::account/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_TwitterAccount::OAUTH_TOKEN:
			case SearchFields_TwitterAccount::OAUTH_TOKEN_SECRET:
			case SearchFields_TwitterAccount::SCREEN_NAME:
			case SearchFields_TwitterAccount::TWITTER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_TwitterAccount::ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
				
			case 'placeholder_bool':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_TwitterAccount::LAST_SYNCED_AT:
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
		return SearchFields_TwitterAccount::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TwitterAccount::OAUTH_TOKEN:
			case SearchFields_TwitterAccount::OAUTH_TOKEN_SECRET:
			case SearchFields_TwitterAccount::SCREEN_NAME:
			case SearchFields_TwitterAccount::TWITTER_ID:
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_TwitterAccount::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_TwitterAccount::LAST_SYNCED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case 'placeholder_bool':
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
					//$change_fields[DAO_TwitterAccount::EXAMPLE] = 'some value';
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
			list($objects,$null) = DAO_TwitterAccount::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_TwitterAccount::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!empty($change_fields)) {
				DAO_TwitterAccount::update($batch_ids, $change_fields);
			}

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_TwitterAccount::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};
