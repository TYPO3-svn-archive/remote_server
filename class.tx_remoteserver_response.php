<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Benjamin Mack <mack@xnos.org>
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
 * Class for assembling a response to a remote call
 * and sending back the proper content and headers
 *
 * This largely based on the work done by Benjamin Mack for class typo3/classes/class.typo3ajax.php
 * The main change is a downgrading of syntax to PHP4 to work on older systems
 *
 * @author	Francois Suter <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	remote_server
 */
class tx_remoteserver_response {
	var $ajaxId        = null;
	var $errorMessage  = null;
	var $isError       = false;
	var $content       = array();
	var $contentFormat = 'plain';
	var $charset       = 'utf-8';

	/**
	 * PHP4 wrapper for constructor
	 *
	 * @param	string		the AJAX id
	 * @return	void
	 */
	function tx_remoteserver_response($ajaxId) {
		$this->__construct($ajaxId);
	}

	/**
	 * Constructor
	 * sets the charset and the ID for the AJAX call
	 *
	 * @param	string		the AJAX id
	 * @return	void
	 */
	function __construct($ajaxId) {
		global $LANG;

		if($LANG->charSet != $this->charset) {
			$this->charset = $LANG->charSet;
		}

		$this->ajaxId = $ajaxId;
	}


	/**
	 * returns the ID for the AJAX call
	 *
	 * @return	string		the AJAX id
	 */
	function getAjaxID() {
		return $this->ajaxId;
	}


	/**
	 * overwrites the existing content with the first parameter
	 *
	 * @param	array		the new content
	 * @return	mixed		the old content as array; if the new content was not an array, false is returned
	 */
	function setContent($content) {
		$oldcontent = false;
		if (is_array($content)) {
			$oldcontent = $this->content;
			$this->content = $content;
		}
		return $oldcontent;
	}


	/**
	 * adds new content
	 *
	 * @param	string		the new content key where the content should be added in the content array
	 * @param	string		the new content to add
	 * @return	mixed		the old content; if the old content didn't exist before, false is returned
	 */
	function addContent($key, $content) {
		$oldcontent = false;
		if (array_key_exists($key, $this->content)) {
			$oldcontent = $this->content[$key];
		}
		if (!isset($content) || !strlen($content)) {
			unset($this->content[$key]);
		} elseif (!isset($key) || !strlen($key)) {
			$this->content[] = $content;
		} else {
			$this->content[$key] = $content;
		}
		return $oldcontent;
	}


	/**
	 * returns the content for the ajax call
	 *
	 * @return	mixed		the content for a specific key or the whole content
	 */
	function getContent($key = '') {
		return ($key && array_key_exists($key, $this->content) ? $this->content[$key] : $this->content);
	}


	/**
	 * sets the content format for the ajax call
	 *
	 * @param	string		can be one of 'plain' (default), 'xml', 'json', 'jsonbody' or 'jsonhead'
	 * @return	void
	 */
	function setContentFormat($format) {
		if (t3lib_div::inArray(array('plain', 'xml', 'json', 'jsonhead', 'jsonbody'), $format)) {
			$this->contentFormat = $format;
		}
	}


	/**
	 * sets an error message and the error flag
	 *
	 * @param	string		the error message
	 * @return	void
	 */
	function setError($errorMsg = '') {
		$this->errorMessage = $errorMsg;
		$this->isError = true;
	}


	/**
	 * checks whether an error occured during the execution or not
	 *
	 * @return	boolean		whether this AJAX call had errors
	 */
	function isError() {
		return $this->isError;
	}


	/**
	 * renders the AJAX call based on the $contentFormat variable and exits the request
	 *
	 * @return	void
	 */
	function render() {
		if ($this->isError) {
			$this->renderAsError();
			exit;
		}
		switch ($this->contentFormat) {
			case 'jsonhead':
			case 'jsonbody':
			case 'json':
				$this->renderAsJSON();
				break;
			case 'xml':
				$this->renderAsXML();
				break;
			default:
				$this->renderAsPlain();
		}
		exit;
	}


	/**
	 * renders the AJAX call in XML error style to handle with JS
	 * the "responseXML" of the transport object will be filled with the error message then
	 *
	 * @return	void
	 */
	function renderAsError() {
		header('Content-type: text/xml; charset='.$this->charset);
		header('X-JSON: false');
		die('<t3err>'.htmlspecialchars($this->errorMessage).'</t3err>');
	}


	/**
	 * renders the AJAX call with text/html headers
	 * the content will be available in the "responseText" value of the transport object
	 *
	 * @return	void
	 */
	function renderAsPlain() {
		header('Content-type: text/html; charset='.$this->charset);
		header('X-JSON: true');
		echo implode('', $this->content);
	}


	/**
	 * renders the AJAX call with text/xml headers
	 * the content will be available in the "responseXML" value of the transport object
	 *
	 * @return	void
	 */
	function renderAsXML() {
		header('Content-type: text/xml; charset='.$this->charset);
		header('X-JSON: true');
		$xmlString = implode('', $this->content);
			// Use SimpleXML (if available) utility to check XML validity
		if (function_exists('simplexml_load_string') && @simplexml_load_string($xmlString)) {
				// String is valid XML, return it as is
			echo $xmlString;
		}
		else {
				// String is not XML (or could not be verified), use array2xml to convert content
			echo t3lib_div::array2xml($this->content);
		}
	}


	/**
	 * renders the AJAX call with JSON evaluated headers
	 * note that you need to have requestHeaders: {Accept: 'application/json'},
	 * in your AJAX options of your AJAX request object in JS
	 *
	 * the content will be available
	 *    - in the second parameter of the onSuccess / onComplete callback (except when contentFormat = 'jsonbody')
	 *    - and in the xhr.responseText as a string (except when contentFormat = 'jsonhead')
	 *         you can evaluate this in JS with xhr.responseText.evalJSON();
	 *
	 * @return	void
	 */
	function renderAsJSON() {
		$content = $this->array2json($this->content);

		header('Content-type: application/json; charset='.$this->charset);
		header('X-JSON: '.($this->contentFormat != 'jsonbody' ? $content : true));

			// bring content in xhr.responseText except when in "json head only" mode
		if ($this->contentFormat != 'jsonhead') {
			echo $content;
		}
	}

 	/**
	 * Creates recursively a JSON literal from a mulidimensional associative array.
	 * Uses Services_JSON (http://mike.teczno.com/JSON/doc/)
	 * Taken from t3lib_div in TYPO3 4.2
	 *
	 * @param	array		$jsonArray: The array to be transformed to JSON
	 * @return	string		JSON string
	 */
	function array2json($jsonArray) {
		if (!$GLOBALS['JSON']) {
			require_once(PATH_typo3.'json.php');
			$GLOBALS['JSON'] = t3lib_div::makeInstance('Services_JSON');
		}
		return $GLOBALS['JSON']->encode($jsonArray);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/remote_server/class.tx_remoteserver_response.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/remote_server/class.tx_remoteserver_response.php']);
}
?>
