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
			'users' => json_decode(DevblocksPlatform::getPluginSetting('wgm.twitter', 'users', ''), TRUE),
		);
		$tpl->assign('params', $params);
		$tpl->assign('extensions', $extensions);
		
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
				
				$users = json_decode(DevblocksPlatform::getPluginSetting('wgm.twitter', 'users', ''), true);
				$users[$user['user_id']] = $user;
				
				DevblocksPlatform::setPluginSetting('wgm.twitter', 'users', json_encode($users));
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config/twitter/')));
			}
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
	
};
endif;

class WgmTwitter_API {
	
	const TWITTER_REQUEST_TOKEN_URL = "https://api.twitter.com/oauth/request_token";
	const TWITTER_AUTHENTICATE_URL = "https://api.twitter.com/oauth/authenticate";
	const TWITTER_ACCESS_TOKEN_URL = "https://api.twitter.com/oauth/access_token";
	const TWITTER_PUBLIC_TIMELINE_API = "https://api.twitter.com/1/statuses/public_timeline.json";
	const TWITTER_UPDATE_STATUS_API = "https://api.twitter.com/1/statuses/update.json";
	
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
		
		$this->_fetch($url, 'POST', $params);
	}
	
	public function get($url) {
		$this->_fetch($url, 'GET');
	}
	
	private function _fetch($url, $method = 'GET', $params = array()) {
		switch($method) {
			case 'POST':
				$method = OAUTH_HTTP_METHOD_POST;
				break;
			default:
				$method = OAUTH_HTTP_METHOD_GET;
		}
		
		$this->_oauth->fetch($url, $params, $method);
	}
}

if(class_exists('Extension_DevblocksEventAction')):
class WgmTwitter_EventActionPost extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
			
		$users = DevblocksPlatform::getPluginSetting('wgm.twitter', 'users', '');
		$users = json_decode($users, TRUE);
		
		$tpl->assign('users', $users);
		
		$tpl->display('devblocks:wgm.twitter::events/action_update_status_twitter.tpl');
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, &$values) {
		$twitter = WgmTwitter_API::getInstance();
		
		$users = DevblocksPlatform::getPluginSetting('wgm.twitter', 'users');
		$users = json_decode($users, TRUE);
		
		$user = $users[$params['user']];
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $values))) {

			$twitter->setCredentials($user['oauth_token'], $user['oauth_token_secret']);
			$twitter->post(WgmTwitter_API::TWITTER_UPDATE_STATUS_API, $content);
			
		}
	}
};
endif;
