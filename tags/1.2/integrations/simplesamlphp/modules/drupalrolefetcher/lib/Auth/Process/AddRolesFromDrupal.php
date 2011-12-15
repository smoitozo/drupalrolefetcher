<?php
// +---------------------------------------------------+
// | PHP Version: 5.2.x                                |
// +---------------------------------------------------+
// | simpleSAMLphp Auth Proc for adding additional     |
// | identity attributes by querying an instance of    |
// | Drupal Role Fetcher <http://code.google.com/p/drupalrolefetcher/>
// +---------------------------------------------------+
// |                                                   |
// | This Auth Proc needs the following configuration  |
// | directives set in order to function properly.     |
// |                                                   |
// | 'drf_url' the URL of the DRF instance to query    |
// |                                                   |
// | 'drf_sharedsec' shared secret assigned by the DRF |
// |                                                   |
// | 'user_identifier' the attribute you will use to   |
// |    find the user in Drupal                        |
// |                                                   |
// | 'attribute_name' the attribute you want the Drupal|
// |    roles put into                                 |
// |                                                   |
// | EXAMPLE                                           |
// |                                                   |
// | 'authproc' => array(                              |
// |    50 => array(                                   |
// |    'class' => 'core:AddRolesFromDrupal',          |
// |    'drf_url' => 'https://www.example.com/drf/',   |
// |    'drf_sharedsec' => '1234567890abcdefghijk',    |
// |    'drf_user_identifier' => 'eduPersonPrincipalName',
// |    'new_attribute_name' => 'wwwexamplecomroles',  |
// |    ),                                             |
// | ),                                                |
// |                                                   |
// | This will cause the Auth Proc to query the DRF    |
// | looking for all the roles that the current user   |
// | is assigned. It will take any roles it receives   |
// | and put them into the specified attribute.        |
// +---------------------------------------------------+
// | Author: Steve Moitozo II <steve_moitozo@sil.org>  |
// | Created: 20110710                                 |
// +---------------------------------------------------+


/**
 * Filter to add attributes to the identity by executing a query against an Drupal Role Fetcher
 *
 *
 * @author Steve Moitozo, SIL International
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_drupalrolefetcher_Auth_Process_AddRolesFromDrupal extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * The configuration.
	 *
	 * Associative array of strings.
	 */
	private $config = array();


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		$reqConfigVars = array(
                'drf_url',
        				'drf_sharedsec',
        				'drf_user_identifier',
        				'new_attribute_name',
              );

		foreach($config as $name => $values){
			if(!is_string($name)){
				throw new Exception('Invalid attribute name: ' . var_export($name, TRUE));
			}

			// make sure the name is in the list of required config variables
			if(in_array($name,$reqConfigVars)){
			

				if(is_array($values)){
					throw new Exception('Configuration parameters must not contain arrays. The value for parameter "'.$name.'" is an array.');
				}

				$this->config[$name] = $values;

			}else{
				// unknown config variable, skipping
				throw new Exception('Unknown configuration variable "'.$name.'"');
			}


		}

		$configVarsSet = array_keys($this->config);
		foreach($reqConfigVars as $configVar){
			if(!in_array($configVar, $configVarsSet)){
				throw new Exception('Please provide a value for configuration parameter "'.$configVar.'".');
			}
		}
	}


	/**
	 * Apply filter to add attributes.
	 *
	 * Add attributes from Drupal.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		$attributes =& $request['Attributes'];

		if(!isset($attributes[$this->config['drf_user_identifier']])){
			throw new Exception('The user\'s identity does not have an attribute called "'.$this->config['drf_user_identifier'].'"');
		}

    $arrDrupalRoles = $this->queryDrf($attributes[$this->config['drf_user_identifier']][0]);

    if(is_array($arrDrupalRoles)){
      $attributes[$this->config['new_attribute_name']] = $arrDrupalRoles;
    }

  }


  /**
   * Apply filter to add attributes.
   *
   * Add attributes from Drupal.
   *
   * @param string $uid  The user identifier
   *
   * @return array the Drupal roles
   */
  private function queryDrf($uid) {
    $strDrupalRoles = file_get_contents($this->config['drf_url'] . '?sharedsec=' . $this->config['drf_sharedsec'] . '&userid=' . urlencode($uid) . '&mode=PHP');
    $arrDrupalRoles = unserialize($strDrupalRoles);

    if(is_array($arrDrupalRoles) && count($arrDrupalRoles)){
      return $arrDrupalRoles;
    }
  }
}

?>
