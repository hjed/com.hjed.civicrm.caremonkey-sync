# com.hjed.civicrm.caremonkey_sync

This extension will provide two way sync of groups in CiviCRM and Google Calendar access.
It is intended for people who do not have a google apps domain, if you have google apps I recomend
syncing via the Google Groups API instead. 

Important: if a user has higher access that already then you grant through this system, the access you grant
will be ignored.

This plugin is being developed for [BLUEsat UNSW](http://bluesat.com.au).

This plugin is intended for a specific use case. Assumptions made here may not meet your needs.

Note: this plugin gives users who have access to modify group custom fields effective access to control
access on all calendars in the connected google account. It is intended in future to be able to restrict this.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v5.4+
* CiviCRM 4.7
* com.hjed.civicrm.oauth-sync

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.hjed.civicrm.caremonkey_sync@https://github.com/FIXME/com.hjed.civicrm.caremonkey_sync/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/com.hjed.civicrm.caremonkey_sync.git
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar require google/apiclient:^2.0
cv en caremonkey_sync
```

## Usage

(* FIXME: Where would a new user navigate to get started? What changes would they see? *)

## Known Issues

(* FIXME *)
