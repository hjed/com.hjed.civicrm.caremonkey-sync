<?php
/**
 * Helper Functions for the CareMonkey Api
 */
class CRM_CaremonkeySync_CaremonkeyHelper {

  const CAREMONKEY_API_REST_URL = 'https://groups.caremonkey.com/api/v2/';

  public static function oauthHelper() {
    static $oauthHelperObj = null;
    if($oauthHelperObj == null) {
      $oauthHelperObj = new CRM_OauthSync_OAuthHelper("caremonkey_sync", self::CAREMONKEY_API_REST_URL);
    }
    return $oauthHelperObj;
  }

  /**
   * Calls the caremonkey api to check we are connected
   * Redirects back if successful.
   *
   */
  public static function doOAuthCodeExchange() {
    $authHeader = Civi::settings()->get('caremonkey_api_token');

    // make a request
    $ch = curl_init(self::CAREMONKEY_API_REST_URL . '/organizations');
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'authorization: ' . $authHeader
      ),
      // the token endpoint requires a user agent
      CURLOPT_USERAGENT => 'curl/7.55.1',
    ));
    $response = curl_exec($ch);
    if(curl_errno($ch)) { 
      echo 'Request Error:' . curl_error($ch);
    } else if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 300) {
      echo 'Bad Status Code:' . curl_getinfo($ch, CURLINFO_HTTP_CODE);
      // TODO: handle this better
    } else {
        $data = json_decode($response, true);
        Civi::settings()->set("caremonkey_organisation_id", $data[0]['id']);
        Civi::settings()->set("caremonkey_sync_connected", true);
        self::oauthHelper()->doOAuthCodeExchangeSuccess();
    }

  }

  private static function getOrganisationId() {
    return Civi::settings()->get('caremonkey_organisation_id');
  }

  /**
   * Call a CareMonkey api endpoint
   *
   * @param string $path the path after the CareMonkeybase url
   *  Ex. /rest/api/3/groups/picker
   * @param string $method the http method to use
   * @param array $body the body of the post request
   * @return array | CRM_Core_Error
   */
  public static function callCaremonkeyApi($path, $method = "GET", $body = NULL) {

    // build the url
    $url = self::CAREMONKEY_API_REST_URL . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_CUSTOMREQUEST => $method
    ));
    if($body != NULL) {
      $encodedBody = json_encode($body);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
    }
    curl_setopt_array(
      $ch,
      array( 
        CURLOPT_HTTPHEADER => array(
          'Authorization: ' . Civi::settings()->get('caremonkey_api_token'),
          'Accept: application/json',
          'Content-Type: application/json'
        )
      )
    );

    $response = curl_exec($ch);
    if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 300) {
      print 'Request Error:' . curl_error($ch);
      print '<br/>\nStatus Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE);
      print_r($ch);
      throw new CRM_Extension_Exception("CareMonkey API Request Failed");
      return CRM_Core_Error::createError("Failed to access CareMonkeyAPI");
      // TODO: handle this better
    } else {
      return json_decode($response, true);
    }
  }

  public static function getGroupList() {
    $groups_json = self::callCaremonkeyApi(
      '/organizations/' . self::getOrganisationId() . '/child_groups',
      "GET"
    );
    print_r($groups_json);

    $folderNames = array();
    foreach ($groups_json as $group) {
      $folderNames = array_merge($folderNames, CRM_CaremonkeySync_BAO_CaremonkeySync::createFromGroupsResponse($group));
    }

    return $folderNames;
  }

  /**
   * Retrieves the email address to use with google.
   * Unless otherwise specified this will be the user's default email address
   * @return string|null
   * @throws CiviCRM_API3_Exception
   */
  private static function getContactEmail($contactId) {
    return CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);
  }

  /**
   * Retrieves the user's caremonkey id if one is stored
   * @return int|null
   * @throws CiviCRM_API3_Exception
   */
  private static function getContactCaremonkeyId($contactId) {
    return CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);
  }

  /**
   * Adds the contact to the remote group.
   * If the contact has not been synced before it will add its CareMonkey account details
   * @param $contactId the contact id of the remote contact
   * @param $remoteGroup the remote group name
   */
  public static function addContactToRemoteGroup($contactId, $remoteGroup) {
    // get the remote group
    $remoteGroup = CRM_CaremonkeySync_BAO_CaremonkeySync::getByOptionGroupValue($remoteGroup);

    // check the contact doesn't already have a higher permission in the group
    self::refreshLocalPermissionsCache($remoteGroup->caremonkey_id);

    $contactEmail = self::getContactEmail($contactId);

    if (!self::getContactCaremonkeyId($contactId)) {
      // TODO: create member
    }

    $response = self::callCaremonkeyApi(
      '/groups/' . $remoteGroup->caremonkey_id . '/members', "POST", array(
        'members' => array(
          array(
            "id" => getContactCaremonkeyId($contactId),
            "originating_bucket_id" => self::getOrganisationId()
          )
        )
      )
    );

    self::$groupMemberCache[$remoteGroup->caremonkey_id][$contactId] =
      array($response['id'], $response);
  }

  /**
   * Removes a given contact from a remote group
   * @param int $contactId the contact to remove
   * @param string $remoteGroup the remote group to remove them from
   */
  public static function removeContactFromRemoteGroup(&$contactId, $remoteGroup) {
    $remoteGroupDAO = CRM_CaremonkeySync_BAO_CaremonkeySync::getByOptionGroupValue($remoteGroup);
    self::refreshLocalPermissionsCache($remoteGroupDAO->caremonkey_id);

    if(!key_exists(intval($contactId), self::$groupMemberCache[$remoteGroupDAO->caremonkey_id])) {
      CRM_Core_Error::debug_log_message("Tried to remove user from CareMonkey, but they weren't there");
      return;
    }

    $response = self::callCaremonkeyApi(
      '/groups/' . $remoteGroupDAO->caremonkey_id . '/members',
      "DELETE",
      array(
        "members" => array(
          array(
            "id" => $contactId
          )
        )
      )
    );
  }

  //format is [fileId][contactId] = array(permissionId, jsonObject)
  private static $groupMemberCache = array();

  /**
   * Because we can't lookup an individual permission without getting the whole list, this
   * requests the permissions for the given CareMonkey file id and caches them by role and contactId.
   *
   * This makes delete a lot faster.
   *
   * @param $groupId the CareMonkey id
   * @param bool $forceUpdate
   * @throws CRM_Extension_Exception
   */
  private static function refreshLocalPermissionsCache($groupId, $forceUpdate=false, $createUsers=false) {

    if(!$forceUpdate && key_exists($groupId, self::$groupMemberCache)) {
      return;
    }

    $response = self::callCaremonkeyApi('/groups/' . $groupId . '/members');
    // TODO: error handling
    foreach($response as $member) {
        $contact = self::findContactFromCaremonkeyUser($member);
        print_r($member);
        print("\n----\n");

        if (!key_exists($groupId, self::$groupMemberCache)) {
          self::$groupMemberCache[$groupId] = array();
        }

        // the last updated is the max of these two
        $last_updated = date_create($member['updated_at']);
        print($member['updated_at']);
        if($member['profile'] != null) {
          $profile_last_updated = date_create($member['profile']['updated_at']);
          $max_last_updated = max($last_updated, $profile_last_updated);
        } else {
          $max_last_updated = $last_updated;
        }
        print("ittr");
        print_r($last_updated);
        print_r($max_last_updated);

        if ($contact && $contact['caremonkey_user_details.caremonkey_last_synced'] != null && 
          $last_updated > $contact['caremonkey_user_details.caremonkey_last_synced']){
          print "update users";

          $builder = \Civi\Api4\Contact::update()
            ->addWhere('id', '=', $contact['id']);

          self::updateOrCreateMember($builder, $member, 'update', [['contact_id', '=', $contact['id']], ['is_primary', '=', '1']], $max_last_updated);
        }

        print_r($conact);
        print_r($contact == null);
        print_r($createUsers);
        if($contact == null && $createUsers) {
          print "create users";

          $builder = \Civi\Api4\Contact::create()
            ->addValue('contact_type', 'Individual');

          self::updateOrCreateMember($builder, $member, 'create', null, $max_last_updated);

          if(count($result) > 0) {
            $contact = $result[0];
          }
        }

        if ($contact != null) {
          self::$groupMemberCache[$groupId][$contact['id']] = array(
            $member['id'], $member
          );
        }
      
    }

  }

  /**
   * Builds a request to the civicrm api to either update or create a contact out of civicrm data
   */
  public static function updateOrCreateMember($builder, $member, $chainMode, $whereClause, $max_last_updated) {
    print_r($builder);
    if($member["integration_id"]) {
      print("iid");
      $builder = $builder->addValue('external_identifier', $member["integration_id"]);
      $builder = $builder->addValue('caremonkey_user_details.caremonkey_intergration_id', $member["integration_id"]);
    }
    $builder = $builder->addValue('caremonkey_user_details.caremonkey_id', $member["id"]);
    $builder = $builder->addValue('caremonkey_user_details.caremonkey_last_synced', $max_last_updated->format(DateTimeInterface::ISO8601));

    $chain = array(
      'emails' =>  ['Email', 'replace', ['records' => [['contact_id' => '$id', 'email' => $member['email']]]]]
    );
    if($whereClause != null) {
      $chain['emails'][2]['where'] = $whereClause;
    }

    if($member['profile'] != null) {
      if(key_exists('mobile_phone', $member['profile'])) {
        $chain['phones'] = ['Phone', 'replace', ['records' => [['contact_id' => '$id', 'phone' => $member['profile']['mobile_phone']]]]]; 
        if($whereClause != null) {
          $chain['phones'][2]['where'] = $whereClause;
        }
      }

      if(key_exists('street', $member['profile'])) {
        $chain['addresses'] = [
          'Address',
          'replace',
          [
            'records' => [[
              'contact_id' => '$id',
                'location_type_id' => 1,
                'street_address' => $member['profile']['street']
              ]],
          ]
        ];
        if($whereClause != null) {
          $chain['addresses'][2]['where'] = $whereClause;
        }
        if(key_exists('city', $member['profile'])) {
          $chain['addresses'][2]['records'][0]['city'] = $member['profile']['city'];
        }
        if(key_exists('zip', $member['profile'])) {
          $chain['addresses'][2]['records'][0]['postal_code'] = $member['profile']['zip'];
        }
        if(key_exists('state', $member['profile'])) {
          $result = \Civi\Api4\StateProvince::get()
            ->addWhere('name', '=', $member['profile'][''])
            ->setLimit(1)
            ->execute();
          if(count($result) > 0) {
            $chain['addresses'][2]['records'][0]['state'] = $result[0]['id'];
          }
        }
      }
    }
    print_r($chain);

    $result = $builder->setChain($chain)
      ->execute();
    print("called\n");
  }

  /**
   * Looks up a contact based on caremonkey identifiers, and then on email if those can't be found.
   * Returns null if no contact is found.
   */
  public static function findContactFromCaremonkeyUser($caremonkeyUser) {
    $contacts = \Civi\Api4\Contact::get()
        ->setSelect([
          'id', 
          'caremonkey_user_details.caremonkey_id', 
          'caremonkey_user_details.caremonkey_integration_id', 
          'caremonkey_user_details.caremonkey_last_synced', 
          'first_name', 
          'middle_name', 
          'last_name', 
          'phones.phone', 
          'phones.is_primary', 
          'emails.email', 
          'emails.is_primary',
      ])
      ->addClause('OR', ['caremonkey_user_details.caremonkey_id', '=', $caremonkeyUser['id']], ['caremonkey_user_details.caremonkey_integration_id', '=', $caremonkeyUser['integration_id']])
      ->setLimit(1)
      // sync should happen regardless which user triggers it
      ->setCheckPermissions(FALSE)
      ->execute();
    if (count($contacts) > 0) {
      return $contacts[0];
    } else {
      return CRM_Contact_BAO_Contact::matchContactOnEmail($member['email']);
    }
  }

  /**
   * given an 'remote group' value representing a group/role return all associated contacts
   * @param $remoteGroup
   * @return array
   * @throws CRM_Extension_Exception
   */
  public static function getAllCaremonkeyMembersForRoleAndGroup($remoteGroup, $createUsers=false) {
    $contactIds = array();
    $remoteGroupDAO = CRM_CaremonkeySync_BAO_CaremonkeySync::getByOptionGroupValue($remoteGroup);
    self::refreshLocalPermissionsCache($remoteGroupDAO->caremonkey_id, false, $createUsers);
    foreach(self::$groupMemberCache[$remoteGroupDAO->caremonkey_id] as $contactId => $member) {
        $contactIds[] = $contactId;
        print("\n---entry:\n");
        print_r($contactId);
        print_r($member);
        print("\n---\n");
    }
    return $contactIds;
  }
}


