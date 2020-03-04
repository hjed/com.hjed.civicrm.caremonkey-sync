<?php
/**
 * Created by IntelliJ IDEA.
 * User: hjed
 * Date: 9/09/18
 * Time: 6:04 PM
 */

return array(
    'caremonkey_api_token' => array(
      'group_name' => 'CaremonkeySync Settings',
      'group' => 'caremonkey',
      'name' => 'caremonkey_api_token',
      'type' => 'String',
      'add' => '4.4',
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'The Caremonkey v2 API token we are using',
      'title' => 'Caremonkey API Token',
      'help_text' => 'Caremonkey doesn\'t actually support oauth, so we need an api token',
      'html_type' => 'Text',
      'html_attributes' => array(
        'size' => 50,
      ),
      'quick_form_type' => 'Element',
    ),
    'caremonkey_organisation_id' => array(
      'group_name' => 'CaremonkeySync Settings',
      'group' => 'caremonkey',
      'name' => 'caremonkey_organisation_id',
      'type' => 'String',
      'add' => '4.4',
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'The Caremonkey Organisation Id for the organisation we are syncing with',
      'title' => 'Caremonkey Organisation Id',
      'help_text' => 'The ID of the organisation',
      'html_type' => 'Text',
      'html_attributes' => array(
        'size' => 50,
      ),
      'quick_form_type' => 'Element',
    )
) + CRM_OAuthSync_Settings::generateSettings('caremonkey_sync', 'CareMonkey Sync');

?>