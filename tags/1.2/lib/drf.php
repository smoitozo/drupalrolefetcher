<?php
/**
 * @file
 * Drupal Role Fetcher
 *
 * Quick and dirty middleware app that returns roles of Drupal users.
 *
 * @author Steve Moitozo <steve_moitozo@sil.org>
 *
 * Copyright SIL International
 * Licensed under the GPL v2.0
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Written for the Polder Consortium
 * http://www.polderconsortium.org
 *
 * Project Web site
 * http://code.google.com/p/drupalrolefetcher/
 *
 * See the README for more information.
 *
 * Basic rule of execution: die silently, unless in debug mode.
 */
// SANITY CHECKS =======================================================

// Require SSL
if (!isset($_SERVER['HTTPS'])) {
  bailout('All request must come over HTTPS.');
}

// Make sure we know where Drupal is and which site to bootstrap when it's time.
define('DRUPAL_ROOT', $CONFIG['drupal_path']);
if (!file_exists(DRUPAL_ROOT)) {
  bailout('Can\'t find Drupal.');
}

// Two parameters are required for servicing requests.

// 1) Validate the shared secret (sharedsec).

if (!(isset($_REQUEST['sharedsec']) && $_REQUEST['sharedsec'])) {
   bailout('The request is missing the required shared secret.');
}

if(!is_array($CONFIG['authorized_agents'])) {
  bailout('No authorized agents have been configured.');
}

// Build an array of authorized shared secrets.
foreach($CONFIG['authorized_agents'] as $arrAgent) {
  $arrSharedSecrets[] = $arrAgent['shared_secret'];
}

if (!is_array($arrSharedSecrets)) {
  bailout('Failed to locate any viable shared secrets in the configuration.');
}

if (!in_array($_REQUEST['sharedsec'], $arrSharedSecrets)) {
  bailout('Authentication failed.');
}

// 2) Check the user identifier (uid, name, email, or authname).

if (!(
        isset($_REQUEST['uid']) && $_REQUEST['uid'] ||
        isset($_REQUEST['name']) && $_REQUEST['name'] ||
        isset($_REQUEST['mail']) && $_REQUEST['mail'] ||
        isset($_REQUEST['authname']) && $_REQUEST['authname'] ||
        isset($_REQUEST['userid']) && $_REQUEST['userid'] // this is deprecated and replaced by authname
      )) {
  bailout('The request is missing a user identifier.');
}

// EXECUTION =======================================================

// We're still running, bootstrap Drupal.
require_once(DRUPAL_ROOT . '/includes/bootstrap.inc');

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// We need to be able to call Drupal user_external_load function so load the required modules.
drupal_load('module', 'system');
drupal_load('module', 'user');

// Find the Drupal user.

// If the Drupal uid (users.uid) is provided
if(isset($_REQUEST['uid'])){
   $user = user_load($_REQUEST['uid']);

// If the name (users.name) is provided
}elseif(isset($_REQUEST['name'])){
   $user = user_load_by_name($_REQUEST['name']);

// If the email (users.mail) is provided
}elseif(isset($_REQUEST['mail'])){
   $user = user_load_by_mail($_REQUEST['mail']);

// If the authname (authmap.authname) is provided
}elseif(isset($_REQUEST['authname'])){
   $user = user_external_load($_REQUEST['authname']);

// If the userid (authmap.authname) is provided
// NOTE: The use of userid is deprecated in favor of authname
}elseif(isset($_REQUEST['userid'])){
   $user = user_external_load($_REQUEST['userid']);
}

// All real Drupal user's have an uid higher than 0 and names.
if ((isset($user->uid) && 0 == $user->uid) || !isset($user->name)) {
  bailout('Failed to locate Drupal user.');
}

// Grab the user's roles.
$arrUserRoles = $user->roles;

// We need to supress the stock Drupal roles: "authenticated user" and "administrator".
foreach($arrUserRoles as $strRole) {
  if (!($strRole === 'administrator' || $strRole === 'authenticated user')) {
    $arrSanitizedUserRoles[] = $strRole . '@' . $CONFIG['realm'];
  }
}

// Serialize the roles according to the agent's wishes.
$strMode = null;
if (isset($_REQUEST['mode']) && $_REQUEST['mode']) {
  $strMode = $_REQUEST['mode'];
}

$strRoles = serializeRoles($arrSanitizedUserRoles, $strMode);

// Output the roles and exit. Our work here is done.
die($strRoles);



// HELPER FUNCTIONS =================================================



/**
 * Cease execution.
 *
 * If debug mode is turned on the script will display the error and
 * exit. Otherwise it will exit silently.
 *
 * @param
 *  the error
 */
function bailout($strError=null) {
  global $CONFIG;

  if (isset($CONFIG['debug']) && $CONFIG['debug']) {
    die($strError);
  }
  else {
    die;
  }
}



/**
 * Serializes the array of roles.
 *
 * @param
 *  The array of roles.
 *
 * @param
 *  The type of serialization (CSV, PHP, JSON).
 *
 * @return
 *  The serialized string of roles.
 */
function serializeRoles($arrRoles, $strMode=CSV) {
  $strRoles = null;

  switch($strMode) {

    case 'CSV':
        $strRoles = roles2Csv($arrRoles);
        break;

    case 'PHP':
        $strRoles = serialize($arrRoles);
        break;

    case 'JSON':
        $strRoles = json_encode($arrRoles);
         break;

   default:
        $strRoles = roles2Csv($arrRoles);

  }

  return $strRoles;
}



/**
 * Serializes roles into a CSV string.
 *
 * @param
 *  The array of roles.
 *
 * @return
 *  The serialized string of roles.
 */
function roles2Csv($arrRoles) {
  $strReturn = NULL;

  if (is_array($arrRoles)) {

    foreach($arrRoles as $strRole) {
      $strReturn .= '"' . $strRole . '",';
    }

    // trim off the trailing comma
    $strReturn = substr($strReturn, 0, strlen($strReturn)-1);
  }

  return $strReturn;
}