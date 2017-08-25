<?php
if(class_exists('Extension_PageMenuItem')):
class WgmTwitter_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmtwitter.setup.menu.plugins.twitter';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.twitter::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmTwitter_MessageProfileSection extends Extension_PageSection {
	const ID = 'cerberusweb.profiles.twitter.message';
	
	function render() {
	}
	
	// [TODO] This should be handled by the context
	function showPeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('view_id', $view_id);
		
		// Message
		
		if(null != ($message = DAO_TwitterMessage::get($id))) {
			$tpl->assign('message', $message);
		}
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_TwitterMessage::ID, false);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(Context_TwitterMessage::ID, $message->id);
		if(isset($custom_field_values[$message->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$message->id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Template
		
		$tpl->display('devblocks:wgm.twitter::tweet/peek.tpl');
	}
	
	function savePeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_reply = DevblocksPlatform::importGPC($_REQUEST['do_reply'], 'integer', 0);
		@$reply = DevblocksPlatform::importGPC($_REQUEST['reply'], 'string', '');
		@$is_closed = DevblocksPlatform::importGPC($_REQUEST['is_closed'], 'integer', 0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($id) || null == ($message = DAO_TwitterMessage::get($id)))
			return;
		
		if(!$message->connected_account_id || false == ($connected_account = DAO_ConnectedAccount::get($message->connected_account_id)))
			return;
			
		if(!Context_ConnectedAccount::isReadableByActor($connected_account, $active_worker))
			return;
		
		$fields = array(
			DAO_TwitterMessage::IS_CLOSED => $is_closed ? 1 : 0,
		);
		
		DAO_TwitterMessage::update($message->id, $fields);
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(Context_TwitterMessage::ID, $message->id, $field_ids);
		
		// Replies
		if(!empty($do_reply) && !empty($reply)) {
			$twitter = WgmTwitter_API::getInstance();
			if(false == ($credentials = $connected_account->decryptParams($active_worker)))
				return;
			
			$twitter->setCredentials($credentials['oauth_token'], $credentials['oauth_token_secret']);
			
			$post_data = array(
				'status' => $reply,
				'in_reply_to_status_id' => $message->twitter_id,
			);
			
			$twitter->post(WgmTwitter_API::TWITTER_UPDATE_STATUS_API, $post_data);
		}
	}
	
	function viewMarkClosedAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		try {
			if(is_array($row_ids))
			foreach($row_ids as $row_id) {
				$row_id = intval($row_id);
				
				if(!empty($row_id))
					DAO_TwitterMessage::update($row_id, array(
						DAO_TwitterMessage::IS_CLOSED => 1,
					));
			}
		} catch (Exception $e) {
			//
		}
		
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		$view->render();
		
		exit;
	}
	
	function showBulkUpdatePopupAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);

		if(!empty($ids)) {
			$id_list = DevblocksPlatform::parseCsvString($ids);
			$tpl->assign('ids', implode(',', $id_list));
		}
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(Context_TwitterMessage::ID, false);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:wgm.twitter::tweet/bulk.tpl');
	}
	
	function startBulkUpdateJsonAction() {
		// Filter: whole list or check
		@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
		
		// View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		$view->setAutoPersist(false);
		
		// Fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string',''));

		$do = array();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Do: Status
		if(0 != strlen($status)) {
			switch($status) {
				default:
					$do['status'] = intval($status);
					break;
			}
		}
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
				@$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
				
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
				
			default:
				break;
		}

		// If we have specific IDs, add a filter for those too
		if(!empty($ids)) {
			$view->addParam(new DevblocksSearchCriteria(SearchFields_TwitterMessage::ID, 'in', $ids));
		}
		
		// Create batches
		$batch_key = DAO_ContextBulkUpdate::createFromView($view, $do);
		
		header('Content-Type: application/json; charset=utf-8');
		
		echo json_encode(array(
			'cursor' => $batch_key,
		));
		
		return;
	}
}
endif;

if(class_exists('Extension_PageSection')):
class WgmTwitter_SetupSection extends Extension_PageSection {
	const ID = 'wgmtwitter.setup.twitter';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'twitter');
		
		$credentials = DevblocksPlatform::getPluginSetting('wgm.twitter','credentials',false,true,true);
		$tpl->assign('credentials', $credentials);
		
		$sync_account_ids = DevblocksPlatform::getPluginSetting('wgm.twitter', 'sync_account_ids_json', [], true);
		
		if(is_array($sync_account_ids) && !empty($sync_account_ids)) {
			$sync_accounts = DAO_ConnectedAccount::getIds($sync_account_ids);
			$tpl->assign('sync_accounts', $sync_accounts);
		}
		
		// Template
		
		$tpl->display('devblocks:wgm.twitter::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			@$sync_account_ids = DevblocksPlatform::importGPC($_REQUEST['sync_account_ids'],'array',[]);
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the Consumer Key and Secret are required.");
			
			$credentials = [
				'consumer_key' => $consumer_key,
				'consumer_secret' => $consumer_secret,
			];
			DevblocksPlatform::setPluginSetting('wgm.twitter','credentials',$credentials,true,true);
			
			$sync_account_ids = DevblocksPlatform::sanitizeArray($sync_account_ids, 'int');
			DevblocksPlatform::setPluginSetting('wgm.twitter', 'sync_account_ids_json', $sync_account_ids, true);
			
			echo json_encode(array('status'=>true,'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};
endif;

class WgmTwitter_API {
	const TWITTER_REQUEST_TOKEN_URL = "https://api.twitter.com/oauth/request_token";
	const TWITTER_AUTHENTICATE_URL = "https://api.twitter.com/oauth/authenticate";
	const TWITTER_ACCESS_TOKEN_URL = "https://api.twitter.com/oauth/access_token";
	
	const TWITTER_PUBLIC_TIMELINE_API = "https://api.twitter.com/1.1/statuses/home_timeline.json";
	const TWITTER_UPDATE_STATUS_API = "https://api.twitter.com/1.1/statuses/update.json";
	const TWITTER_STATUSES_MENTIONS_API = "https://api.twitter.com/1.1/statuses/mentions_timeline.json";
	
	static $_instance = null;
	private $_oauth = null;
	
	private function __construct() {
		if(false == ($credentials = DevblocksPlatform::getPluginSetting('wgm.twitter', 'credentials', false, true, true)))
			return;
		
		@$consumer_key = $credentials['consumer_key'];
		@$consumer_secret = $credentials['consumer_secret'];
		
		$this->_oauth = DevblocksPlatform::services()->oauth($consumer_key, $consumer_secret);
	}
	
	/**
	 * @return WgmTwitter_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmTwitter_API();
		}

		return self::$_instance;
	}
	
	public function setCredentials($token, $secret) {
		$this->_oauth->setTokens($token, $secret);
	}
	
	public function getRequestToken($callback_url) {
		return $this->_oauth->getRequestTokens(self::TWITTER_REQUEST_TOKEN_URL, $callback_url);
	}
	
	public function getAuthenticationUrl($request_token) {
		return $this->_oauth->getAuthenticationURL(self::TWITTER_AUTHENTICATE_URL, $request_token);
	}
	
	public function getAccessToken($verifier) {
		return $this->_oauth->getAccessToken(self::TWITTER_ACCESS_TOKEN_URL, array('oauth_verifier' => $verifier));
	}
	
	public function post($url, $params) {
		return $this->_execute($url, 'POST', $params);
	}
	
	public function get($url) {
		return $this->_execute($url, 'GET');
	}
	
	public function authenticateHttpRequest(&$ch, &$verb, &$url, &$body, &$headers) {
		return $this->_oauth->authenticateHttpRequest($ch, $verb, $url, $body, $headers);
	}
	
	private function _execute($url, $method = 'GET', $params = array()) {
		// [TODO] Response object?
		return $this->_oauth->executeRequest($method, $url, $params);
	}
};

if(class_exists('CerberusCronPageExtension')):
class Cron_WgmTwitterChecker extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::services()->log('Twitter Checker');
		$twitter = WgmTwitter_API::getInstance();
		$db = DevblocksPlatform::services()->database();
		
		$logger->info("Started");
		
		$sync_account_ids = DevblocksPlatform::getPluginSetting('wgm.twitter', 'sync_account_ids_json', [], true);
		
		if(!is_array($sync_account_ids) || empty($sync_account_ids))
			return;
		
		$accounts = DAO_ConnectedAccount::getIds($sync_account_ids);
		
		foreach($accounts as $account) {
			$logger->info(sprintf("Checking mentions for @%s", $account->name));
			
			$credentials = $account->decryptParams();
			
			$twitter->setCredentials($credentials['oauth_token'], $credentials['oauth_token_secret']);
			
			$twitter_url = WgmTwitter_API::TWITTER_STATUSES_MENTIONS_API . '?count=150';
			
			$max_id = $db->GetOneMaster(sprintf("SELECT MAX(CAST(twitter_id as unsigned)) FROM twitter_message WHERE connected_account_id = %d", $account->id));
			
			if($max_id)
				$twitter_url .= sprintf("&since_id=%s", $max_id);
			
			$out = $twitter->get($twitter_url);
			
			// [TODO] Handle utf8mb4
		
			if(false !== ($json = @json_decode($out, true))) {
				foreach($json as $message) {
					$fields = array(
						DAO_TwitterMessage::CONNECTED_ACCOUNT_ID => $account->id,
						DAO_TwitterMessage::TWITTER_ID => $message['id_str'],
						DAO_TwitterMessage::TWITTER_USER_ID => $message['user']['id_str'],
						DAO_TwitterMessage::CREATED_DATE => strtotime($message['created_at']),
						DAO_TwitterMessage::IS_CLOSED => 0,
						DAO_TwitterMessage::USER_NAME => $message['user']['name'],
						DAO_TwitterMessage::USER_SCREEN_NAME => $message['user']['screen_name'],
						DAO_TwitterMessage::USER_PROFILE_IMAGE_URL => $message['user']['profile_image_url'],
						DAO_TwitterMessage::USER_FOLLOWERS_COUNT => $message['user']['followers_count'],
						DAO_TwitterMessage::CONTENT => $message['text'],
					);
					
					$tweet_id = DAO_TwitterMessage::create($fields);
					
					$logger->info(sprintf("Saved mention #%d from %s", $tweet_id, $message['user']['screen_name']));
				}
			}
		}
		
		$logger->info("Finished");
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->cache_lifetime = "0";
	}
	
	public function saveConfigurationAction() {
	}
};
endif;

class WgmTwitter_EventActionPost extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);

		$connected_accounts = DAO_ConnectedAccount::getReadableByActor($active_worker, ServiceProvider_Twitter::ID);
		$tpl->assign('connected_accounts', $connected_accounts);
		
		$tpl->display('devblocks:wgm.twitter::events/action_post_to_twitter.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$connected_account_id = DevblocksPlatform::importVar($params['connected_account_id'], 'int', 0);
		
		if(!$connected_account_id || false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id))) {
			return "[ERROR] A Twitter connected account isn't configured.";
		}
		
		if(false == ($credentials = $connected_account->decryptParams()))
			return "[ERROR] Failed to decrypt connected account credentials.";
		
		@$token = $credentials['oauth_token'];
		@$token_secret = $credentials['oauth_token_secret'];
		
		if(empty($token)) {
			return "[ERROR] The connected Twitter account has an invalid token.";
		}
		
		if(empty($token_secret)) {
			return "[ERROR] The connected Twitter account has an invalid token secret.";
		}
		
		// [TODO] Test Twitter API connection
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			$out = sprintf(">>> Posting to Twitter using %s:\n%s\n",
				$connected_account->name,
				$content
			);
		} else {
			return "[ERROR] Template failed to parse.";
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$twitter = WgmTwitter_API::getInstance();
		
		@$connected_account_id = DevblocksPlatform::importVar($params['connected_account_id'], 'int', 0);
		
		if(false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			return;
		
		$credentials = $connected_account->decryptParams();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			@$token = $credentials['oauth_token'];
			@$token_secret = $credentials['oauth_token_secret'];
			
			if(empty($token) || empty($token_secret))
				return;
			
			$twitter->setCredentials($token, $token_secret);
			
			$post_data = array(
				'status' => $content,
			);
			
			$twitter->post(WgmTwitter_API::TWITTER_UPDATE_STATUS_API, $post_data);
			
		}
	}
};

class ServiceProvider_Twitter extends Extension_ServiceProvider implements IServiceProvider_OAuth, IServiceProvider_HttpRequestSigner {
	const ID = 'wgm.twitter.service.provider';
	
	function renderConfigForm(Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('account', $account);
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.twitter::provider/twitter.tpl');
	}
	
	function saveConfigForm(Model_ConnectedAccount $account, array &$params) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		$encrypt = DevblocksPlatform::services()->encryption();
		
		// Decrypt OAuth params
		if(isset($edit_params['params_json'])) {
			if(false == ($outh_params_json = $encrypt->decrypt($edit_params['params_json'])))
				return "The connected account authentication is invalid.";
				
			if(false == ($oauth_params = json_decode($outh_params_json, true)))
				return "The connected account authentication is malformed.";
			
			if(is_array($oauth_params))
			foreach($oauth_params as $k => $v)
				$params[$k] = $v;
		}
		
		return true;
	}
	
	private function _getAppKeys() {
		if(false == ($credentials = DevblocksPlatform::getPluginSetting('wgm.twitter','credentials',false,true,true)))
			return false;
		
		@$consumer_key = $credentials['consumer_key'];
		@$consumer_secret = $credentials['consumer_secret'];
		
		if(empty($consumer_key) || empty($consumer_secret))
			return false;
		
		return array(
			'key' => $consumer_key,
			'secret' => $consumer_secret,
		);
	}
	
	function oauthRender() {
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'], 'string', '');
		
		// Store the $form_id in the session
		$_SESSION['oauth_form_id'] = $form_id;
		
		$url_writer = DevblocksPlatform::services()->url();

		if(false == ($app_keys = $this->_getAppKeys())) {
			echo DevblocksPlatform::strEscapeHtml("ERROR: The consumer key and secret aren't configured in Setup->Services->Twitter.");
			return false;
		}
		
		$oauth = DevblocksPlatform::services()->oauth($app_keys['key'], $app_keys['secret']);
		
		// OAuth callback
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_Twitter::ID), true);
		
		$tokens = $oauth->getRequestTokens(WgmTwitter_API::TWITTER_REQUEST_TOKEN_URL, $redirect_url);
		
		// [TODO] We need to pass through the actual error
		if(!isset($tokens['oauth_token'])) {
			echo DevblocksPlatform::strEscapeHtml("ERROR: Twitter didn't return an access token.");
			return false;
		}
		
		$url = $oauth->getAuthenticationURL(WgmTwitter_API::TWITTER_AUTHENTICATE_URL, $tokens['oauth_token']);
		
		header('Location: ' . $url);
	}
	
	// [TODO] Verify the caller?
	function oauthCallback() {
		$form_id = $_SESSION['oauth_form_id'];
		unset($_SESSION['oauth_form_id']);
		
		$encrypt = DevblocksPlatform::services()->encryption();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(false == ($app_keys = $this->_getAppKeys())) {
			echo DevblocksPlatform::strEscapeHtml("ERROR: The consumer key and secret aren't configured in Setup->Services->Twitter.");
			return false;
		}
		
		$oauth_token = $_REQUEST['oauth_token'];
		$oauth_verifier = $_REQUEST['oauth_verifier'];
		
		$oauth = DevblocksPlatform::services()->oauth($app_keys['key'], $app_keys['secret']);
		$oauth->setTokens($oauth_token);
		
		$params = $oauth->getAccessToken(WgmTwitter_API::TWITTER_ACCESS_TOKEN_URL, array('oauth_verifier' => $oauth_verifier));
		
		if(!is_array($params) || !isset($params['screen_name'])) {
			echo DevblocksPlatform::strEscapeHtml("ERROR: We couldn't retrieve your username from the Twitter API.");
			return false;
		}
		
		// Output
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('form_id', $form_id);
		$tpl->assign('label', $params['screen_name']);
		$tpl->assign('params_json', $encrypt->encrypt(json_encode($params)));
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/oauth_callback.tpl');
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['oauth_token'])
			|| !isset($credentials['oauth_token_secret'])
		)
			return false;
		
		$twitter = WgmTwitter_API::getInstance();
		$twitter->setCredentials($credentials['oauth_token'], $credentials['oauth_token_secret']);
		return $twitter->authenticateHttpRequest($ch, $verb, $url, $body, $headers);
	}
}