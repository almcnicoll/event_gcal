<?php
namespace Drupal\event_gcal\Form;

const EVENT_GCAL_APPNAME = "Drupal Event <=> Google Calendar integration";
const EVENT_GCAL_CLIENTID_DEFAULT = '36313660344-751ap95mln269b79n5lk8qgd1elaaegg.apps.googleusercontent.com';
const EVENT_GCAL_CLIENTSECRET_DEFAULT = 'YTQvk_qa-MpHf6kwFEHMtU1h';

function getRedirectUrl() {
	global $debugInfo;
	
	$destSuffix = ((isset($debugInfo['test_URLs']))?'test':'settings_oauth_receive');
	
	$localhost_list = array('127.0.0.1', "::1", 'localhost');
	if(in_array($_SERVER['REMOTE_ADDR'], $localhost_list)){
		$redirect_url = "http://localhost/drupal8-dev/admin/config/event_gcal/{$destSuffix}";
		$debugInfo['domain'] = $_SERVER['REMOTE_ADDR'].' (local)';
	} else {
		$domain = (empty($_SERVER['HTTP_HOST'])?$_SERVER['SERVER_NAME']:$_SERVER['HTTP_HOST']);
		$redirect_url = "http://{$domain}/admin/config/event_gcal/{$destSuffix}";
		$debugInfo['domain'] = $domain.' (remote)';
	}
	return $redirect_url;
}

function getClient($redirect_on_oauth_failure = false, &$config = null, $client_id = null, $client_secret = null, $access_token = null, $redirect_url = null) {
	/*$module_handler = \Drupal::service('module_handler');
	$module_path = $module_handler->getModule('event_gcal')->getPath();
	$client_secret_path = '/'.$module_path.'/conf/client_secret.json';*/
	
	if ((empty($client_id)) && (!empty($config))) {
		$client_id = $config->get('client_id');
	}
	if ((empty($client_secret)) && (!empty($config))) {
		$client_secret = $config->get('client_secret');
	}
	
	$client = new \Google_Client();
	$client->setAccessType('offline'); // default: offline
	$client->setIncludeGrantedScopes(true);
	$client->addScope(\Google_Service_Calendar::CALENDAR);
	$client->setApplicationName(EVENT_GCAL_APPNAME);
	//$client->setAuthConfig($client_secret_path);
	$client->setClientId(isset($client_id) ? $client_id : EVENT_GCAL_CLIENTID_DEFAULT);
	$client->setClientSecret(isset($client_secret) ? $client_secret : EVENT_GCAL_CLIENTSECRET_DEFAULT);
	
	// Check access token
	if (empty($access_token)) {
		// Check whether to redirect
		if ($redirect_on_oauth_failure) {
			if (empty($redirect_url)) {
				$redirect_url = getRedirectUrl();
			}
			$client->setRedirectUri($redirect_url);
			$authUrl = $client->createAuthUrl();
			if (headers_sent()) {
				echo "<div>Google Calendar authentication failure. Please click <a href='{$authUrl}'>here</a> to re-authorise.</div>";
				return $authUrl;
			} else {
				header("Location: {$authUrl}");
				exit();
			}
		} else {
			// No access token, return null for failure
			return null;
		}
	} else {
		$client->setAccessToken($access_token);
	}
	
	// Check expiry
	if ($client->isAccessTokenExpired()) {
		// Refresh token
		$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
		$access_token = $client->getAccessToken();
		if ($config == null) {
			$config = \Drupal::config('event_gcal.settings');
		}
		// Write refreshed token to config
		$config->set('access_token', $access_token)->save();
	}
	
	return $client;		
}


function contentTypeFields($contentType) {
    $entityManager = \Drupal::service('entity.manager');
    $fields = [];

    if(!empty($contentType)) {
        /*$fields = array_filter(
            $entityManager->getFieldDefinitions('node', $contentType), function ($field_definition) {
                return $field_definition instanceof FieldConfigInterface;
            }
        );*/
		$fields = $entityManager->getFieldDefinitions('node', $contentType);
    }

    return $fields;      
}