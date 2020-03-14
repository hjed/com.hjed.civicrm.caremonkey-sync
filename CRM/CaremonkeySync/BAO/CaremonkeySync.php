<?php
use CRM_CaremonkeySync_ExtensionUtil as E;

class CRM_CaremonkeySync_BAO_CaremonkeySync extends CRM_CaremonkeySync_DAO_CaremonkeySync {

  /**
   * Create a new CaremonkeySync based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_CaremonkeySync_DAO_CaremonkeySync|NULL
   */
  public static function create($params) {
    $className = 'CRM_CaremonkeySync_DAO_CaremonkeySync';
    $entityName = 'CaremonkeySync';
    $hook = 'create';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Create a set of new CaremonkeySync based on a CareMonkey object
   * and its possible roles
   *
   * @param array $params key-value pairs representing a file in the CareMonkey rest api
   * @return array of role based CaremonkeySync's
   */
  public static function createFromGroupsResponse($params) {
    $groupName = $params['name'];
    $names = array();
    print("<br/><hr/>");
    print("\nparams:");
    print_r($params);
    $dbParams = array(
      'caremonkey_id' => $params['id'],
      'group_name' => $groupName,
      'type' => 'member'
    );

    self::create($dbParams);
    $names[] = $groupName;
    return $names;
  }

  /**
   * Lookup a folder mapping
   * @param $oGroupValue the option group value to search for
   * @return CRM_Core_DAO|object the dao containing the caremonkey_id and the role
   */
  public static function getByOptionGroupValue($oGroupValue) {
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT caremonkey_id, type FROM civicrm_caremonkey_sync WHERE group_name = (%1)",
      array(1 => array($oGroupValue, 'String'))
    );
    $dao->fetch();
    return $dao;
  }

}
