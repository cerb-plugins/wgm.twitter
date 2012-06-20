<?php
if(class_exists('Extension_PageMenuItem')):
class WgmTwitter_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmtwitter.setup.menu.plugins.twitter';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.twitter::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmTwitter_SetupSection extends Extension_PageSection {
	const ID = 'wgmtwitter.setup.twitter';
	
	function render() {
		// check whether extensions are loaded or not
		$extensions = array(
			'oauth' => extension_loaded('oauth')
		);
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'twitter');
		
		$params = array(
			'consumer_key' => DevblocksPlatform::getPluginSetting('wgm.twitter','consumer_key',''),
			'consumer_secret' => DevblocksPlatform::getPluginSetting('wgm.twitter','consumer_secret',''),
		);
		$tpl->assign('params', $params);
		$tpl->assign('extensions', $extensions);
		
		// Worklist

		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'setup_twitter_accounts';
		$defaults->class_name = 'View_TwitterAccount';
		$defaults->name = 'Authorized Accounts';
		
		if(null == ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults)))
			return;
		
		C4_AbstractViewLoader::setView($defaults->id, $view);

		$tpl->assign('view', $view);
		
		// Template
		
		$tpl->display('devblocks:wgm.twitter::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the API Auth Token and URL are required.");
			
			DevblocksPlatform::setPluginSetting('wgm.twitter','consumer_key',$consumer_key);
			DevblocksPlatform::setPluginSetting('wgm.twitter','consumer_secret',$consumer_secret);
			
		    echo json_encode(array('status'=>true,'message'=>'Saved!'));
		    return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
	
	function authAction() {
		@$callback = DevblocksPlatform::importGPC($_REQUEST['_callback'], 'bool', 0);
		@$post = DevblocksPlatform::importGPC($_REQUEST['_post'], 'bool', 0);
		@$denied = DevblocksPlatform::importGPC($_REQUEST['denied'], 'string', '');

		$twitter = WgmTwitter_API::getInstance();
		
		$url = DevblocksPlatform::getUrlService();
		$oauth_callback_url = $url->write('ajax.php?c=config&a=handleSectionAction&section=twitter&action=auth&_callback=true', true);
		
		if($callback) {
			if(!$denied) {
				$twitter->setCredentials($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
				$user = $twitter->getAccessToken();
				
				$result = DAO_TwitterAccount::getByTwitterId($user['user_id']);
				
				$fields = array(
					DAO_TwitterAccount::TWITTER_ID => $user['user_id'],
					DAO_TwitterAccount::SCREEN_NAME => $user['screen_name'],
					DAO_TwitterAccount::OAUTH_TOKEN => $user['oauth_token'],
					DAO_TwitterAccount::OAUTH_TOKEN_SECRET => $user['oauth_token_secret'],
				);
				
				// Check UPDATE or CREATE

				if(!empty($result)) {
					$account_id = key($result);
					DAO_TwitterAccount::update($account_id, $fields);
					
				} else {
					$account_id = DAO_TwitterAccount::create($fields);
				}
			}
			
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','twitter')));
			
		} else {
			try {
				$request_token = $twitter->getRequestToken($oauth_callback_url);
				
				$_SESSION['oauth_token'] = $request_token['oauth_token'];
				$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
				
				header('Location: ' . WgmTwitter_API::TWITTER_AUTHENTICATE_URL . '?oauth_token=' . $request_token['oauth_token']);
				exit;
				
			} catch(OAuthException $e) {
				echo "Exception: " . $e->getMessage();
			}
		}
	}
	
	function showPeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null == ($account = DAO_TwitterAccount::get($id)))
			return;

		$tpl->assign('account', $account);
		
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:wgm.twitter::account/peek.tpl');
	}
	
	function savePeekPopupAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'], 'integer', 0);
		
		if(!empty($do_delete)) {
			DAO_TwitterAccount::delete($id);
			
		} else {
			
		}
		
		return;
	}
};
endif;

class WgmTwitter_API {
	const TWITTER_REQUEST_TOKEN_URL = "https://api.twitter.com/oauth/request_token";
	const TWITTER_AUTHENTICATE_URL = "https://api.twitter.com/oauth/authenticate";
	const TWITTER_ACCESS_TOKEN_URL = "https://api.twitter.com/oauth/access_token";
	const TWITTER_PUBLIC_TIMELINE_API = "https://api.twitter.com/1/statuses/public_timeline.json";
	const TWITTER_UPDATE_STATUS_API = "https://api.twitter.com/1/statuses/update.json";
	const TWITTER_STATUSES_MENTIONS_API = "https://api.twitter.com/1/statuses/mentions.json";
	
	static $_instance = null;
	private $_oauth = null;
	
	private function __construct() {
		$consumer_key = DevblocksPlatform::getPluginSetting('wgm.twitter','consumer_key','');
		$consumer_secret = DevblocksPlatform::getPluginSetting('wgm.twitter','consumer_secret','');
		$this->_oauth = new OAuth($consumer_key, $consumer_secret);
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
		$this->_oauth->setToken($token, $secret);
	}
	
	public function getAccessToken() {
		return $this->_oauth->getAccessToken(self::TWITTER_ACCESS_TOKEN_URL);
	}
	
	public function getRequestToken($callback_url) {
		return $this->_oauth->getRequestToken(self::TWITTER_REQUEST_TOKEN_URL, $callback_url);
	}
	
	public function post($url, $content) {
		$params = array(
			'status' => $content,		
		);
		
		return $this->_fetch($url, 'POST', $params);
	}
	
	public function get($url) {
		return $this->_fetch($url, 'GET');
	}
	
	private function _fetch($url, $method = 'GET', $params = array()) {
		switch($method) {
			case 'POST':
				$method = OAUTH_HTTP_METHOD_POST;
				break;
				
			default:
				$method = OAUTH_HTTP_METHOD_GET;
				break;
		}

		$this->_oauth->fetch($url, $params, $method);
		
		//var_dump($this->_oauth->getLastResponseInfo());
		
		return $this->_oauth->getLastResponse();
	}
};

if(class_exists('CerberusCronPageExtension')):
class Cron_WgmTwitterChecker extends CerberusCronPageExtension {
	public function run() {
		$logger = DevblocksPlatform::getConsoleLog('Twitter Checker');
		$logger->info("Started");

		$twitter = WgmTwitter_API::getInstance();
		
		$accounts = DAO_TwitterAccount::getAll();
		
		foreach($accounts as $account) { /* @var $account Model_TwitterAccount */
			$logger->info(sprintf("Checking mentions for @%s", $account->screen_name));
			
			try {
				$twitter->setCredentials($account->oauth_token, $account->oauth_token_secret);
				
				$twitter_url = 'https://api.twitter.com/1/statuses/mentions.json?count=150';
				
				if(!empty($account->last_synced_msgid))
					$twitter_url .= sprintf("&since_id=%s", $account->last_synced_msgid);
				
				$out = $twitter->get($twitter_url);
			
				if(false !== ($json = @json_decode($out, true))) {
					foreach($json as $message) {
						$fields = array(
							DAO_TwitterMessage::ACCOUNT_ID => $account->id,
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
					
					$fields = array(
						DAO_TwitterAccount::LAST_SYNCED_AT => time(),
					);
					
					// Store the last message-id for incremental updates
					if(null != ($msg = reset($json))) {
						$fields[DAO_TwitterAccount::LAST_SYNCED_MSGID] = $msg['id_str'];
					}
					
					DAO_TwitterAccount::update($account->id, $fields);
				}
				
			} catch(OAuthException $e) { /* @var $e Exception */
				$logger->error($e->getMessage());
			}
		}
		
		$logger->info("Finished");
	}
	
	public function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
	}
	
	public function saveConfigurationAction() {
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class WgmTwitter_EventActionPost extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);

		$accounts = DAO_TwitterAccount::getAll();
		$tpl->assign('twitter_accounts', $accounts);
		
		$tpl->display('devblocks:wgm.twitter::events/action_update_status_twitter.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$accounts = DAO_TwitterAccount::getAll();

		if(!isset($accounts[$params['user']])) {
			return "[ERROR] No Twitter user selected.";
		}
		
		$account = $accounts[$params['user']];
		
		// [TODO] Test Twitter API connection
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			$out = sprintf(">>> Posting to Twitter for @%s:\n%s\n",
				$account->screen_name,
				$content
			);
		} else {
			return "[ERROR] Template failed to parse.";
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$twitter = WgmTwitter_API::getInstance();
		$accounts = DAO_TwitterAccount::getAll();

		if(null == ($account = @$accounts[$params['user']]))
			return;
	
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			$twitter->setCredentials($account->oauth_token, $account->oauth_token_secret);
			
			$post_data = array(
				'status' => $content,
			);
			
			$twitter->post(WgmTwitter_API::TWITTER_UPDATE_STATUS_API, $post_data);
			
		}
	}
};
endif;
