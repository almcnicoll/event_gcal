<?php

/**
 * @file
 * Contains \Drupal\event_gcal\Form\event_gcalSettingsForm
 */
namespace Drupal\event_gcal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/event_gcalShared.php';

/**
 * Configure event_gcal settings for this site.
 */
class event_gcalSettingsForm extends ConfigFormBase
{
    /** 
   * {@inheritdoc}
   */
    public function getFormId() 
    {
        return 'event_gcal_admin_settings';
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

    /** 
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state) 
    {
        global $debugInfo;
        if (empty($debugInfo)) { $debugInfo = array(); 
        }
    
        // Load form-tweaks library
        $form['#attached']['library'][] = 'event_gcal/form-tweaks';
    
        $config = $this->config('event_gcal.settings');
    
        $activated = $config->get('activated');
        $google_account = $config->get('google_account');
        $client_id = $config->get('client_id');
        $client_secret = $config->get('client_secret');
        $access_token = $config->get('access_token');
    
        if (!empty($access_token)) {
              // As long as we have an access token, check if it's an object, and if not then try to json-decode it
            if (is_object($access_token)) {
                $access_token = json_decode(json_decode($access_token), true);
            
            } elseif (is_array($access_token)) {
                // All good
            } else {
                $access_token = json_decode($access_token, true);
            }
        }
        //print_r($access_token);
        //die();
    
        if (isset($_REQUEST['clear_tokens'])) {
              // Clear tokens, then redirect to self to lose the request string (otherwise the tokens continue to be cleared)
              $config->clear('access_token')->clear('refresh_token')->save();
              $access_token = null;
              $refresh_token = null;
              drupal_set_message('Tokens cleared from module configuration.');
              $url = './settings';
              return new \Symfony\Component\HttpFoundation\RedirectResponse($url);
        }
    
        $available_calendars = array();
        $calendar_options = array();
    
        if ($activated == 1
            && !empty($google_account)
            && !empty($client_id)
            && !empty($client_secret)
            && !empty($access_token)
        ) {
              // It's active and we have credentials that should be valid
        
              // Try to get list of Google Calendars	
              $scope = implode(' ', array(\Google_Service_Calendar::CALENDAR));
              $client = getClient(true, $config, $client_id, $client_secret, $access_token);
                
              $service = new \Google_Service_Calendar($client);
            try {
                $calendar_list = $service->calendarList->listCalendarList();
            } catch (\Google_Service_Exception $gse) {
                $exception_message = json_decode($gse->getMessage());
                if (is_object($exception_message->error)) {
                    switch($exception_message->error->code) {
                    case '401':
                        drupal_set_message("The credentials passed to the Google API were invalid.: ".$gse->getMessage());
                        //$config->clear('access_token')->clear('refresh_token')->save();
                        break;
                       break;
                    default:
                        break;
                    }
                } else {
                    switch ($exception_message->error) {
                    case 'invalid_grant':
                        drupal_set_message("Access to this Google Account has been revoked. You will need to re-authorise this application by re-saving the credentials: ".$gse->getMessage());
                        $config->clear('access_token')->save();
                        break;
                    case 'unauthorized_client':
                        $rendered_message = \Drupal\Core\Render\Markup::create("It appears as though your token needs a refresh and this has failed. You may need to <a href='./settings?clear_tokens=true'>clear your tokens</a> and try again: ".$gse->getMessage());
                        //drupal_set_message(t("It appears as though your token needs refreshing and this has failed. You may need to @link and try again: ",array('@link' => $clear_tokens_link)));
                        drupal_set_message($rendered_message);
                        //dpm($access_token);
                        $refreshResult = $client->fetchAccessTokenWithRefreshToken($access_token['refresh_token']);
                        dpm($refreshResult);
                        break;
                    default:
                        drupal_set_message("Error accessing calendar list: ".$gse->getMessage());                    
                        break;
                    }
                }
            } catch (\Exception $e) {
                $debugInfo['list_exception'] = $e;
                drupal_set_message("Error accessing calendar list: ".$e->getMessage());
            }
              //$debugInfo['calendar_list'] = $calendar_list->getItems();
            if (is_object($calendar_list)) {
                $valid_roles = array('writer','owner'); // Possible return values: freeBusyReader / reader / writer / owner
                foreach ($calendar_list->getItems() as $i=>$cal) {
                    $cal_access = $cal->accessRole;
                    if (in_array($cal_access, $valid_roles)) {
                        // Add to list
                        $available_calendars[] = $cal;
                        $calendar_options[$cal->id] = $cal->summary;
                    }
                }
            }
              //$debugInfo['calendar_list'] = $available_calendars;
        } else {
            if ($activated == 1) {
                $client = getClient(true, $config);
            } else {
                drupal_set_message("Google account not yet successfully linked/activated. You will need to do this in order to see the list of calendars.");
            }
        }
        /*$info_array = array(
        'google_account' => $google_account,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'access_token' => $access_token,
        );
        dpm($info_array);*/
    
        // Try to get list of content types
        $entityManager = \Drupal::service('entity.manager');
        $contentTypes = $entityManager->getStorage('node_type')->loadMultiple();
        $contentTypesList = [];
        $contentTypeFields = [];
        $contentTypeFieldsList = [];
        foreach ($contentTypes as $contentType) {
              //dpm($contentType);
              $ct_id = $contentType->id();
              $contentTypesList[$ct_id] = $contentType->label();
              $contentTypeFields[$ct_id] = contentTypeFields($ct_id);
              //drupal_set_message($ct_id);
              //dpm($contentTypeFields[$ct_id]);
            foreach ($contentTypeFields[$ct_id] as $k=>$ctf) {
                $label = $ctf->getLabel();
                if (empty($label)) { $label = $k; 
                }
                //drupal_set_message($ct_id . '.' . $k . ': ' . $label);
                $contentTypeFieldsList[$ct_id.'.'.$k] = "{$label} ({$ct_id}.{$k})";
            }
        }
        //dpm($contentTypeFieldsList);
    

        $form['event_gcal_activated'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Activated'),
        '#default_value' => $activated,
        );
        $form['event_gcal_google_account'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Google Account'),
        '#default_value' => $google_account,
        );
        $form['event_gcal_client_id'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Client ID'),
        '#default_value' => (empty($client_id) ? EVENT_GCAL_CLIENTID_DEFAULT : $client_id),
        );
        $form['event_gcal_client_secret'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Client Secret'),
        '#default_value' => (empty($client_secret) ? EVENT_GCAL_CLIENTSECRET_DEFAULT : $client_secret),
        );
        $form['event_gcal_active_calendar'] = array(
        '#type' => 'select',
        '#title' => $this->t('Active Calendar'),
        '#options' => $calendar_options,
        '#default_value' => $config->get('active_calendar'),
        );
        $form['event_gcal_active_content_type'] = array(
        '#type' => 'select',
        '#title' => $this->t('Active Content Type'),
        '#options' => $contentTypesList,
        '#default_value' => $config->get('active_content_type'),
        /*'#ajax' => [
        'callback' => array($this, 'validateEmailAjax'),
        'event' => 'change',
        'progress' => array(
        'type' => 'throbber',
        'message' => t('Filtering fields'),
        ),
        ],*/
        );
        $active_content_type = $config->get('active_content_type');
        $filtered_fields = array_flip(
            array_filter(
                array_flip($contentTypeFieldsList), function ($key) use ($active_content_type) {
                    //drupal_set_message($key);
                    return (substr($key, 0, strlen($active_content_type)) == $active_content_type);
                }
            )
        );
        $form['event_gcal_field_title'] = array(
        '#type' => 'select',
        '#title' => $this->t('Title Field'),
        '#options' => $contentTypeFieldsList,
        '#default_value' => $config->get('field_title'),
        '#attributes' => array('class' => array('filter-on-content-type')),
        );
        $form['event_gcal_field_start'] = array(
        '#type' => 'select',
        '#title' => $this->t('Start-time Field'),
        '#options' => $contentTypeFieldsList,
        '#default_value' => $config->get('field_start'),
        '#attributes' => array('class' => array('filter-on-content-type')),
        );
        $form['event_gcal_field_end'] = array(
        '#type' => 'select',
        '#title' => $this->t('End-time Field'),
        '#options' => $contentTypeFieldsList,
        '#default_value' => $config->get('field_end'),
        '#attributes' => array('class' => array('filter-on-content-type')),
        );
        $form['event_gcal_field_sync'] = array(
        '#type' => 'select',
        //'#required' => true,
        '#title' => $this->t('Sync storage field'),
        '#description' => $this->t('A field used solely by this module for storing sync data with Google Calendar. It should probably be hidden in all displays.'),
        '#options' => $contentTypeFieldsList,
        '#default_value' => $config->get('field_sync'),
        '#attributes' => array('class' => array('filter-on-content-type')),
        );
    
        //if (!empty($debugInfo)) { dpm($debugInfo); }

        return parent::buildForm($form, $form_state);
    }

    /** 
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state) 
    {
        global $debugInfo;
        $debugInfo = array();
    
        $config = \Drupal::service('config.factory')->getEditable('event_gcal.settings');
    
        // Have any auth fields been manually changed? (if so, don't try old tokens)
        $auth_details_changed = (
        ($config->get('google_account') != $form_state->getValue('event_gcal_google_account')) || 
        ($config->get('client_id') != $form_state->getValue('event_gcal_client_id')) || 
        ($config->get('client_secret') != $form_state->getValue('event_gcal_client_secret'))
        );
    
        // Save all settings apart from tokens
        $config
            ->set('activated', $form_state->getValue('event_gcal_activated'))
            ->set('google_account', $form_state->getValue('event_gcal_google_account'))
            ->set('client_id', $form_state->getValue('event_gcal_client_id'))
            ->set('client_secret', $form_state->getValue('event_gcal_client_secret'))
            ->set('active_calendar', $form_state->getValue('event_gcal_active_calendar'))
            ->set('active_content_type', $form_state->getValue('event_gcal_active_content_type'))
            ->set('field_title', $form_state->getValue('event_gcal_field_title'))
            ->set('field_start', $form_state->getValue('event_gcal_field_start'))
            ->set('field_end', $form_state->getValue('event_gcal_field_end'))
            ->set('field_sync', $form_state->getValue('event_gcal_field_sync'))
            ->save();
    
        $access_token = $config->get('access_token');
    
        $activated = $form_state->getValue('event_gcal_activated');
    
        parent::submitForm($form, $form_state);
    }
}
