<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter <typo3@cobweb.ch>
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
 * This simple class just contains a single method for answering to test HELO requests
 *
 * @author	Francois Suter <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	remote_server
 */
class tx_remoteserver_helo {

	/**
	 * This method responds to calls to remote_server::helo
	 * This is a test service that just sends back the "HELO" string as a plain text content
	 *
	 * @param		array	$params: Empty array :-)
	 * @param		object	$callingObj: Response object
	 *
	 * @return	void
	 */
	function helo($params, &$callingObj) {
		$callingObj->setContentFormat('plain');
		$callingObj->addContent('', 'HELO');
	}
}