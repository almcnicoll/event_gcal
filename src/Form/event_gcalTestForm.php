<?php

/**
 * @file
 * Contains \Drupal\event_gcal\Form\event_gcalSettingsForm
 */
namespace Drupal\event_gcal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

require_once (__DIR__ . '/../../vendor/autoload.php');
require_once (__DIR__ . '/event_gcalShared.php');

/**
 * Configure event_gcal settings for this site.
 */
class event_gcalTestForm extends ConfigFormBase {
	var $test_steps = array(
		0	=>	'Start',
		1	=>	'Get auth token',
		2	=>	'Exchange auth token for access token',
		3	=>	'Attempt token refresh',
	);
	
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_gcal_admin_test';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'event_gcal.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	global $debugInfo;
	
	$debugInfo['test_URLs'] = true;
	
	if (empty($debugInfo)) { $debugInfo = array(); }
	
	$test_step = $_SESSION['event_gcal_test_step'];
	$form['#title'] = "Testing - " . $this->test_steps[$test_step] . " ({$test_step})";
	
	// Load form-tweaks library
	//$form['#attached']['library'][] = 'event_gcal/form-tweaks';
	
    $config = $this->config('event_gcal.settings');
	
	$activated = $config->get('activated');
	$google_account = $config->get('google_account');
	$client_id = $config->get('client_id');
	$client_secret = $config->get('client_secret');
	$access_token = $config->get('access_token');
	$refresh_token = $config->get('refresh_token');
	
	//dpm($test_step);
	$form['#prefix'] = "Step {$test_step}:<br /><hr /><br />\n";
	
	/*
	if ($test_step > count($this->test_steps)) {
		drupal_set_message("Exceeded maximum test step.");
		$_SESSION['event_gcal_test_step'] = 0;
		$test_step = 0;
	}
	*/
	
	// Display section
	switch ($test_step) {
		case 0:
			// Display client_id, client_secret
			// Submission will return authCode
			$_SESSION['debugInfo'] = array(); // Clear full session log
			$form['#prefix'] .= "Client ID: {$client_id}<br />";
			$form['#prefix'] .= "Client secret: {$client_secret}";
			break;
		case 1:
			// Display authCode
			// Submission will return AccessToken and RefreshToken
			if (isset($_REQUEST['code'])) {
				$authCode = $_REQUEST['code'];
			} else {
				$authCode = '<no code returned>';
				drupal_set_message("Code not returned from Google API. Cannot continue to next step.");
				$_SESSION['event_gcal_test_step'] = 0;
			}
			$form['#prefix'] .= "AuthCode: {$authCode}<br />";
			//$form['#prefix'] .= "Client secret: {$client_secret}";
			$form['event_gcal_auth_code'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('AuthCode'),
				'#default_value' => $authCode,
			);
			break;
		case 2:
			// Display AccessToken and RefreshToken
			// Submission will try to refresh AccessToken
			$form['#prefix'] .= "AccessToken: {$access_token}<br />";
			$form['#prefix'] .= "RefreshToken: {$refresh_token}<br />";
			$form['event_gcal_access_token'] = array(
				'#type' => 'textfield',
				'#maxlength' => 1024,
				'#title' => $this->t('Access Token'),
				'#default_value' => $access_token,
			);
			$form['event_gcal_refresh_token'] = array(
				'#type' => 'textfield',
				'#maxlength' => 1024,
				'#title' => $this->t('Refresh Token'),
				'#default_value' => $refresh_token,
			);
			break;
		case 2:
			// Display AccessToken and RefreshToken again
			// Submission will do nothing
			$form['#prefix'] .= "AccessToken: {$access_token}<br />";
			$form['#prefix'] .= "RefreshToken: {$refresh_token}<br />";
			$form['event_gcal_access_token'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Access Token'),
				'#default_value' => $access_token,
			);
			$form['event_gcal_refresh_token'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Refresh Token'),
				'#default_value' => $refresh_token,
			);
			$form['event_gcal_access_token2'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Access Token #2'),
				'#default_value' => $access_token_2,
			);
			/*$form['event_gcal_refresh_token2'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Refresh Token #2'),
				'#default_value' => $refresh_token_2,
			);*/
			break;
		default:
			// Display full log
			dpm($_SESSION['debugInfo']);
			break;
	}
	
	// Display test step
	$form['event_gcal_test_step'] = array(
		'#type' => 'textfield',
		'#title' => $this->t('Test stage'),
		//'#description' => $this->t('A field used solely by this module for storing sync data with Google Calendar. It should probably be hidden in all displays.'),
		//'#options' => $contentTypeFieldsList,
		'#default_value' => ((int)$_SESSION['event_gcal_test_step'])+1,
    );
	
	
	if (!empty($debugInfo)) {
		//dpm($debugInfo);
		$SESSION['debugInfo']["Step {$test_step}"] = $debugInfo;
	}
	
	drupal_set_message('Full debug trace');
	dpm($_SESSION['debugInfo']);

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	global $debugInfo;
	if (empty($debugInfo)) { $debugInfo = array(); }
	
	//$test_step = $_SESSION['event_gcal_test_step'];
	$test_step = $form_state->getValue('event_gcal_test_step');
	
	$debugInfo['test_URLs'] = true;
	
	$config = \Drupal::service('config.factory')->getEditable('event_gcal.settings');
	
	$_SESSION['event_gcal_test_step'] = $form_state->getValue('event_gcal_test_step');
	
	// Take relevant action
	drupal_set_message("Submission step {$test_step}.");
	switch($test_step) {
		case 0:
			// Shouldn't happen
			break;
		case 1:
			// Submission will return authCode
			$client = getClient($client_id, $client_secret);
			$rVal = getAuthCode($client);
			$debugInfo['codeReturn'] = $rVal;
			$redir = $rVal['authUrl'];
			//die($redir);
			break;
		case 2:
			// Submission will return AccessToken and RefreshToken
			$authCode = $form_state->getValue('event_gcal_auth_code');
			if (!empty($authCode)) {
				$client = getClient($client_id, $client_secret);
				$rVal = getToken($client, $authCode);
				$debugInfo['tokenReturn'] = $rVal;
				//error_log(print_r($rVal, true));
				dpm($rVal);
				$config->set('access_token', $rVal['access_token'])->save();
				if (!empty($rVal['refresh_token'])) {
					$config->set('refresh_token', $rVal['refresh_token'])->save();
				}
			} else {
				drupal_set_message("Token not returned from Google API. Cannot continue to next step.");
				$_SESSION['event_gcal_test_step'] = 0;
			}
			$debugInfo['tokens'] = $rVal;
			break;
		case 3:
			// Submission will try to refresh AccessToken
			$access_token= $config->get('access_token');
			$refresh_token= $config->get('refresh_token');
			$client = getClient($client_id, $client_secret);
			$rVal = doRefresh($client, $access_token, $refresh_token, true);
			//dpm($rVal);
			//error_log(print_r($rVal, true));
			$access_token['access_token']['access_token'] = $rVal['access_token'];
			$access_token['access_token']['created'] = $rVal['created'];
			$config->set('access_token', $access_token)->save();
			break;
		default:
			// Do nothing
			break;
	}
	
	//$redir = null;
	//$redir = $form_state->getValue('event_gcal_form_redirect');
	if (isset($redir)) {
		$form_state->setResponse(new \Drupal\Core\Routing\TrustedRedirectResponse($redir));
	}
	
    parent::submitForm($form, $form_state);
  }
}