<?php

/**
 * Handles CareMonkeyoauth callback
 */
use CRM_CaremonkeySync_ExtensionUtil as E;

class CRM_CaremonkeySync_Page_OAuthCallback extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('OAuthCallback'));

    $state_array = explode('!', $_GET['state']);
    // TODO: verify input
    $prefix = $state_array[0];
    $state = $state_array[1];
    $helper = CRM_CaremonkeySync_CaremonkeyHelper::oauthHelper();
    //verify the callback
    if($helper->verifyState($state)) {
      $helper->doOAuthCodeExchange($_GET['code']);
    } else {
      echo "error";
    }

    parent::run();
  }

}
