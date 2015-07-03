<?php
class DAO_TwitterMessage extends Cerb_ORMHelper {
	const ID = 'id';
	const ACCOUNT_ID = 'account_id';
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
		$db->ExecuteMaster($sql);
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
				CerberusContexts::checkpointChanges('cerberusweb.contexts.twitter.message', $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'twitter_message', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.twitter_message.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged('cerberusweb.contexts.twitter.message', $batch_ids);
			}
		}
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
		$sql = "SELECT id, account_id, twitter_id, twitter_user_id, user_name, user_screen_name, user_followers_count, user_profile_image_url, created_date, is_closed, content ".
			"FROM twitter_message ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_TwitterMessage	 */
	static function get($id) {
		if(empty($id))
			return null;
		
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
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TwitterMessage();
			$object->id = $row['id'];
			$object->account_id = $row['account_id'];
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
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	public static function random() {
		return self::_getRandom('twitter_message');
	}
	
	static function deleteByAccounts($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM twitter_message WHERE account_id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM twitter_message WHERE id IN (%s)", $ids_list));
		
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
			"twitter_message.account_id as %s, ".
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
				SearchFields_TwitterMessage::ACCOUNT_ID,
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
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'twitter_message.id',
			$select_sql,
			$join_sql
		);
				
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
			array('DAO_TwitterMessage', '_translateVirtualParameters'),
			$args
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
			
		$from_context = 'cerberusweb.contexts.twitter.message';
		$from_index = 'twitter_message.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			
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
			($has_multiple_values ? 'GROUP BY twitter_message.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
		} else {
			$rs = $db->ExecuteSlave($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs mysqli_result */
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_TwitterMessage::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT twitter_message.id) " : "SELECT COUNT(twitter_message.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

};

class SearchFields_TwitterMessage implements IDevblocksSearchFields {
	const ID = 't_id';
	const ACCOUNT_ID = 't_account_id';
	const TWITTER_ID = 't_twitter_id';
	const TWITTER_USER_ID = 't_twitter_user_id';
	const USER_NAME = 't_user_name';
	const USER_SCREEN_NAME = 't_user_screen_name';
	const USER_FOLLOWERS_COUNT = 't_user_followers_count';
	const USER_PROFILE_IMAGE_URL = 't_user_profile_image_url';
	const CREATED_DATE = 't_created_date';
	const IS_CLOSED = 't_is_closed';
	const CONTENT = 't_content';
	
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'twitter_message', 'id', $translate->_('common.id'), null),
			self::ACCOUNT_ID => new DevblocksSearchField(self::ACCOUNT_ID, 'twitter_message', 'account_id', $translate->_('dao.twitter_message.account_id'), null),
			self::TWITTER_ID => new DevblocksSearchField(self::TWITTER_ID, 'twitter_message', 'twitter_id', $translate->_('dao.twitter_message.twitter_id'), null),
			self::TWITTER_USER_ID => new DevblocksSearchField(self::TWITTER_USER_ID, 'twitter_message', 'twitter_user_id', $translate->_('dao.twitter_message.twitter_user_id'), null),
			self::USER_NAME => new DevblocksSearchField(self::USER_NAME, 'twitter_message', 'user_name', $translate->_('dao.twitter_message.user_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::USER_SCREEN_NAME => new DevblocksSearchField(self::USER_SCREEN_NAME, 'twitter_message', 'user_screen_name', $translate->_('dao.twitter_message.user_screen_name'), Model_CustomField::TYPE_SINGLE_LINE),
			self::USER_FOLLOWERS_COUNT => new DevblocksSearchField(self::USER_FOLLOWERS_COUNT, 'twitter_message', 'user_followers_count', $translate->_('dao.twitter_message.user_followers_count'), Model_CustomField::TYPE_NUMBER),
			self::USER_PROFILE_IMAGE_URL => new DevblocksSearchField(self::USER_PROFILE_IMAGE_URL, 'twitter_message', 'user_profile_image_url', $translate->_('dao.twitter_message.user_profile_image_url'), null),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'twitter_message', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'twitter_message', 'is_closed', $translate->_('dao.twitter_message.is_closed'), Model_CustomField::TYPE_CHECKBOX),
			self::CONTENT => new DevblocksSearchField(self::CONTENT, 'twitter_message', 'content', $translate->_('common.content'), Model_CustomField::TYPE_MULTI_LINE),
				
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
		);
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			'cerberusweb.contexts.twitter.message',
		));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_TwitterMessage {
	public $id;
	public $account_id;
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

class View_TwitterMessage extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'twittermessage';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Twitter Messages');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TwitterMessage::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_TwitterMessage::USER_NAME,
			SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT,
			SearchFields_TwitterMessage::ACCOUNT_ID,
			SearchFields_TwitterMessage::CREATED_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TwitterMessage::ID,
			SearchFields_TwitterMessage::TWITTER_ID,
			SearchFields_TwitterMessage::TWITTER_USER_ID,
			SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET,
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
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_TwitterMessage', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TwitterMessage', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_TwitterMessage::IS_CLOSED:
				case SearchFields_TwitterMessage::USER_NAME:
				case SearchFields_TwitterMessage::USER_SCREEN_NAME:
					$pass = true;
					break;
					
				// Virtuals
 				case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
 					$pass = true;
 					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_TwitterMessage::IS_CLOSED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_TwitterMessage', $column);
				break;
				
			case SearchFields_TwitterMessage::USER_NAME:
			case SearchFields_TwitterMessage::USER_SCREEN_NAME:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_TwitterMessage', $column);
				break;
				
			case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_TwitterMessage', 'cerberusweb.contexts.twitter.message', $column);
				break;
				
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_TwitterMessage', $column, 'twitter_message.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TwitterMessage::CONTENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'account' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TwitterMessage::ACCOUNT_ID),
					'examples' => array(
						'cerb',
					),
			),
			'content' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TwitterMessage::CONTENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_TwitterMessage::CREATED_DATE),
				),
			'isClosed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_TwitterMessage::IS_CLOSED),
				),
			'followers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TwitterMessage::USER_FOLLOWERS_COUNT),
				),
			'screenName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TwitterMessage::USER_SCREEN_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'userName' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TwitterMessage::USER_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext('cerberusweb.contexts.twitter.message', $fields, null);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				case 'account':
					$field_keys = array(
						'account' => SearchFields_TwitterMessage::ACCOUNT_ID,
					);
					
					@$field_key = $field_keys[$k];
					
					$oper = DevblocksSearchCriteria::OPER_IN;
					
					$patterns = DevblocksPlatform::parseCsvString($v);
					$accounts = DAO_TwitterAccount::getAll();
					$values = array();
					
					if(is_array($patterns))
					foreach($patterns as $pattern) {
						foreach($accounts as $account_id => $account) {
							if(false !== stripos($account->screen_name, $pattern))
								$values[$account_id] = true;
						}
					}
					
					$param = new DevblocksSearchCriteria(
						$field_key,
						$oper,
						array_keys($values)
					);
					$params[$field_key] = $param;					
					break;
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext('cerberusweb.contexts.twitter.message');
		$tpl->assign('custom_fields', $custom_fields);

		// Accounts
		$twitter_accounts = DAO_TwitterAccount::getAll();
		$tpl->assign('twitter_accounts', $twitter_accounts);
		
		$tpl->assign('view_template', 'devblocks:wgm.twitter::tweet/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
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
				
			case SearchFields_TwitterMessage::ACCOUNT_ID:
				$options = array();

				$accounts = DAO_TwitterAccount::getAll();
				if(is_array($accounts))
				foreach($accounts as $account) {
					$options[$account->id] = $account->screen_name;
				}
				$tpl->assign('options', $options);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
				
			case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, 'cerberusweb.contexts.twitter.message');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_TwitterMessage::IS_CLOSED:
				parent::_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_TwitterMessage::ACCOUNT_ID:
				$accounts = DAO_TwitterAccount::getAll();
				$strings = array();
				
				foreach($values as $account_id) {
					if(isset($accounts[$account_id]))
						$strings[] = $accounts[$account_id]->screen_name;
				}
				
				echo implode(' or ', $strings);
				
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
			case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
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
				
			case SearchFields_TwitterMessage::ACCOUNT_ID:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$options = DevblocksPlatform::sanitizeArray($options, 'integer', array('nonzero','unique'));
				$criteria = new DevblocksSearchCriteria($field,$oper,$options);
				break;
				
			case SearchFields_TwitterMessage::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
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
				case 'status':
					$change_fields[DAO_TwitterMessage::IS_CLOSED] = !empty($v) ? 1 : 0;
					break;
					
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
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
			self::_doBulkSetCustomFields('cerberusweb.contexts.twitter.message', $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_TwitterMessage extends Extension_DevblocksContext {
	const ID = 'cerberusweb.contexts.twitter.message';
	
	function getRandom() {
		return DAO_TwitterMessage::random();
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
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'created',
			'user_name',
			'user_screen_name',
			'user_followers_count',
			'is_closed',
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
		} elseif(is_array($tweet)) {
			$tweet = Cerb_ORMHelper::recastArrayToModel($tweet, 'Model_TwitterMessage');
		} else {
			$tweet = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'content' => $prefix.$translate->_('common.content'),
			'created' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'is_closed' => $prefix.$translate->_('dao.twitter_message.is_closed'),
			'twitter_id' => $prefix.$translate->_('dao.twitter_message.twitter_id'),
			'twitter_url' => $prefix.$translate->_('Twitter URL'),
			'user_followers_count' => $prefix.$translate->_('dao.twitter_message.user_followers_count'),
			'user_name' => $prefix.$translate->_('dao.twitter_message.user_name'),
			'user_profile_image_url' => $prefix.$translate->_('dao.twitter_message.user_profile_image_url'),
			'user_screen_name' => $prefix.$translate->_('dao.twitter_message.user_screen_name'),
			//'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'content' => Model_CustomField::TYPE_SINGLE_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'twitter_id' => Model_CustomField::TYPE_NUMBER,
			'twitter_url' => Model_CustomField::TYPE_URL,
			'user_followers_count' => Model_CustomField::TYPE_NUMBER,
			'user_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'user_profile_image_url' => Model_CustomField::TYPE_URL,
			'user_screen_name' => Model_CustomField::TYPE_SINGLE_LINE,
			//'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_TwitterMessage::ID;
		$token_values['_types'] = $token_types;
		
		if($tweet) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $tweet->user_screen_name . ': ' . $tweet->content;
			$token_values['created'] = $tweet->created_date;
			$token_values['id'] = $tweet->id;
			$token_values['is_closed'] = $tweet->is_closed;
			$token_values['content'] = $tweet->content;
			$token_values['twitter_id'] = $tweet->twitter_id;
			$token_values['twitter_url'] = sprintf("http://twitter.com/%s/status/%s", $tweet->user_screen_name, $tweet->twitter_id);
			$token_values['user_followers_count'] = $tweet->user_followers_count;
			$token_values['user_name'] = $tweet->user_name;
			$token_values['user_profile_image_url'] = $tweet->user_profile_image_url;
			$token_values['user_screen_name'] = $tweet->user_screen_name;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($tweet, $token_values);
			
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
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
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
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
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
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

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
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

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
		return $view;
	}
};