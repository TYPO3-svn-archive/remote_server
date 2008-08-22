<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Remote calls dispatcher
 * @author	Francois Suter <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage remote_server
 */
unset($MCONF);
require_once('conf.php');

// Initialise TYPO3 BE and validate BE user authentication

require_once('init.php');

// Read configuration
$configuration = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['remote_server']);

// Get the service ID requested and the corresponding script
$serviceID = (string)t3lib_div::_GP('serviceID');
$script = $TYPO3_CONF_VARS['EXTCONF']['remoteserver']['services'][$serviceID];

// Create a response object to contain the results of the call

require_once('class.tx_remoteserver_response.php');
$responseClassName = t3lib_div::makeInstanceClassName('tx_remoteserver_response');
$response = new $responseClassName($serviceID);

// Issue errors if no service was defined or requested service was not registered
if (empty($serviceID)) {
	if ($configuration['debug']) t3lib_div::devLog('Remote call with no service', 'remote_server', 3);
	$response->setError('No service selected');
}
elseif (empty($script)) {
	if ($configuration['debug']) t3lib_div::devLog('Remote call with invalid service '.$serviceID, 'remote_server', 3);
	$response->setError('No script corresponding to selected service ('.$serviceID.')');
}

// Otherwise continue
else {
	if ($configuration['debug']) t3lib_div::devLog('Remote call with service '.$serviceID, 'remote_server', 0);
	$parameters = array();
	$ret = t3lib_div::callUserFunction($script, $parameters, $response, false, true);
	if ($ret === false) {
		if ($configuration['debug']) t3lib_div::devLog('Remote call script failed for service '.$serviceID, 'remote_server', 3);
		$response->setError('No script corresponding to selected service ('.$serviceID.')');
	}
	else {
		if ($configuration['debug']) t3lib_div::devLog('Remote call successful for service '.$serviceID, 'remote_server', -1);		
	}
}

// Output the content of the response
$response->render();
?>