<?php
/**
 * @file
 * Configuration file for the Drupal Role Fetcher
 */

// REGISTRY OF AUTHORIZED AGENTS --------------------------------

// Note: one way to generate these keys is using the following command on the unix shell:
//  tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
// Once an agent is configured you need to tell the agent what it's shared secret is.

$CONFIG['authorized_agents'][] = array(
  'shared_secret' => 'sharedsecret',
  'name'          => 'www.example.com/myapp',
  'desc'          => 'MyApp',
  'contact'       => 'webmaster@example.com'
  );

// ONE TIME CONFIGURATION SETTINGS --------------------------------

// Where's Drupal boostrap file?
$CONFIG['drupal_path']  = '/path/to/drupal-7.x';
$CONFIG['drupal_site']  = 'default';
$CONFIG['debug']        = TRUE;

// The domain that will be appended to each role
$CONFIG['realm']  = 'www.example.com';
 
