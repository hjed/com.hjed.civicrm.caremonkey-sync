<?php

/**
 * Handles CareMonkeyoauth callback
 */
use CRM_CaremonkeySync_ExtensionUtil as E;

class CRM_CaremonkeySync_Page_OAuthCallback extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('OAuthCallback'));

    //verify the callback
    if(CRM_CaremonkeySync_CaremonkeyHelper::verifyState($_GET['state'])) {
      CRM_CaremonkeySync_CaremonkeyHelper::doOAuthCodeExchange($_GET['code']);
      echo "success";
    } else {
      echo "error";
    }


    parent::run();
  }

}
