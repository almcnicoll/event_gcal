<?php

/**
 * @file
 * Contains \Drupal\event_gcal\Form\event_gcalSettingsFormOAuth
 */
namespace Drupal\event_gcal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/event_gcalShared.php';

/**
 * Configure event_gcal settings for this site.
 */
class event_gcalSettingsFormOAuth extends ConfigFormBase
{
    /** 
   * {@inheritdoc}
   */
    public function getFormId() 
    {
        return 'event_gcal_admin_settings_oauth_receive';
    }

    /** 
   * {@inheritdoc}
   */
    protected function getEditableConfigNames() 
    {
        return [
         'event_gcal.settings',
        ];
    }
 
    public function getTokenFromAuthCode(&$config, $code = null) 
    {
        global $debugInfo;
        $returnValue = array();
                
        $localhost_list = array('127.0.0.1', "::1", 'localhost');
        if(in_array($_SERVER['REMOTE_ADDR'], $localhost_list)) {
             $redirect_url = 'http://localhost/drupal8-dev/admin/config/event_gcal/settings_oauth_receive';
             $debugInfo['domain'] = $_SERVER['REMOTE_ADDR'].' (local)';
        } else {
             $domain = (empty($_SERVER['HTTP_HOST'])?$_SERVER['SERVER_NAME']:$_SERVER['HTTP_HOST']);
             $redirect_url = "http://{$domain}/admin/config/event_gcal/settings_oauth_receive";
             $debugInfo['domain'] = $domain.' (remote)';
        }

        // Set up client object
        if ((empty($client_id)) && (!empty($config))) {
             $client_id = $config->get('client_id');
        }
        if ((empty($client_secret)) && (!empty($config))) {
             $client_secret = $config->get('client_secret');
        }
        
        if (class_exists('Google_Client')) {
             $scopes = explode(',', \Google_Service_Calendar::CALENDAR);
             // Set up client object
             $client = new \Google_Client();
             $client->setAccessType('offline'); // default: offline
             //$client->setScopes($scopes);
             $client->setApplicationName(EVENT_GCAL_APPNAME);
             //$client->setAuthConfig($client_secret_path);
             $client->setClientId(isset($client_id) ? $client_id : EVENT_GCAL_CLIENTID_DEFAULT);
             $client->setClientSecret(isset($client_secret) ? $client_secret : EVENT_GCAL_CLIENTSECRET_DEFAULT);
             $client->setRedirectUri($redirect_url);
            
             // Exchange code for access token
            try {
                $access_token = $client->fetchAccessTokenWithAuthCode($code);
                if (empty($access_token['error'])) {
                    $config->set('access_token', $access_token)->save();
                } else {
                    $returnValue['error'] = $access_token['error'];
                    return $returnValue;
                }
            } catch (Exception $e) {
                $debugInfo['authenticateError'] = print_r($e, true);
            }
            
            if(empty($access_token)) {
                // No token - there's a problem
                $returnValue['error'] = "No token returned for authCode {$code}";
            } else {
                // Token given
                $returnValue['success'] = true;
            }
        } else {
             $returnValue['error'] = "Cannot find class Google_Client";
        }
        return $returnValue;
    }
  
    /** 
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        global $debugInfo;
        $config = \Drupal::service('config.factory')->getEditable('event_gcal.settings'); //$config = $this->config('event_gcal.settings');
    
        if (isset($_REQUEST['error'])) {
              // Error - they didn't approve it
              drupal_set_message(t("Sorry, but you did not approve access for this application. Please try again if you wish to use the module."));
              $config->set('activated', 0)->save(); // De-activate so we don't go around in a loop
        } else {
              // They approved it - switch auth code for token
              $returnInfo = $this->getTokenFromAuthCode($config, $_REQUEST['code']);
              $debugInfo['tokenExchangeReturnInfo'] = $returnInfo;
        
              $form['event_gcal_access_token'] = array(
             '#type' => 'textfield',
             '#title' => $this->t('Access Token'),
             '#default_value' => $config->get('access_token'),
              );
        
            if (empty($returnInfo['error'])) {
                // All good - redirect
                drupal_set_message("Authorisation information received from Google.");
                $url = './settings';
                $debugInfo['redir_url'] = $url;
                return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
                //return new \RedirectResponse( __DIR__ . '/settings' );
            } else {
                // Error in return from Google
                dpm($debugInfo);
                $config->set('activated', 0)->save(); // De-activate so we don't go around in a loop
                $rendered_message = \Drupal\Core\Render\Markup::create("The token request returned the error(s) shown above. The module has been deactivated. You can try again by clicking <a href='./settings?clear_tokens=true'>here</a>.");
                drupal_set_message($rendered_message);
            }
        }
    
        //dpm($debugInfo);
    
    
        return parent::buildForm($form, $form_state);
    }

    /** 
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        global $debugInfo;
        $config = \Drupal::service('config.factory')->getEditable('event_gcal.settings');
    
        // Save tokens etc.
        $config
            ->set('access_token', $form_state->getValue('event_gcal_access_token'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
