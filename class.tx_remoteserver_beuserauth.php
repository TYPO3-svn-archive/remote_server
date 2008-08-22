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

require_once(PATH_t3lib.'class.t3lib_beuserauth.php');

/**
 * This class extends and partly overrides t3lib_beUserAuth as we must avoid some of the stuff done in that class
 * (see individual method comments for details)
 * 
 * @author	Francois Suter <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage remote_server
 */
class tx_remoteserver_beuserauth extends t3lib_beUserAuth {
	/**
	 * Overload t3lib_userauth::start()
	 * We don't want to do as much stuff as the original method, in particular no session or cookies should be set
	 */
	function start() {
		$this->loginType = 'BE';

			// set level to normal if not already set
		$this->security_level = 'normal';

			// enable dev logging if set
		if ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog']) $this->writeDevLog = TRUE;
		if ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['writeDevLog'.$this->loginType]) $this->writeDevLog = TRUE;
		if (TYPO3_DLOG) $this->writeDevLog = TRUE;

		if ($this->writeDevLog) 	t3lib_div::devLog('## Beginning of auth logging.', 't3lib_userAuth');

		$this->svConfig = $TYPO3_CONF_VARS['SVCONF']['auth'];

		$this->user = '';

			// Check to see if anyone has submitted login-information and if so register the user with the session. $this->user[uid] may be used to write log...
		$this->checkAuthentication();
	}

	/**
	 * Checks if a submission of username and password is present or use other authentication by auth services
	 *
	 * @return	void
	 * @internal
	 */
	function checkAuthentication() {

			// No user for now - will be searched by service below
		$tempuserArr = array();
		$tempuser = FALSE;

			// User is not authenticated by default
		$authenticated = FALSE;

			// User want to login with passed login data (name/password)
		$activeLogin = FALSE;

			// Indicates if an active authentication failed (not auto login)
		$this->loginFailure = FALSE;

		if ($this->writeDevLog) 	t3lib_div::devLog('Login type: '.$this->loginType, 't3lib_userAuth');

			// The info array provide additional information for auth services
		$authInfo = $this->getAuthInfoArray();

			// Get Login/Logout data submitted by a form or params
		$loginData = $this->getLoginFormData();
//t3lib_div::debug($loginData);

		if ($this->writeDevLog) 	t3lib_div::devLog('Login data: '.t3lib_div::arrayToLogString($loginData), 't3lib_userAuth');


			// active logout (eg. with "logout" button)
		if ($loginData['status']=='logout') {
			if ($this->writeStdLog) 	$this->writelog(255,2,0,2,'User %s logged out',Array($this->user['username']));	// Logout written to log
			if ($this->writeDevLog) 	t3lib_div::devLog('User logged out. Id: '.$this->id, 't3lib_userAuth', -1);

			$this->logoff();
		}

			// active login (eg. with login form)
		if ($loginData['status']=='login') {
			$activeLogin = TRUE;

			if ($this->writeDevLog) 	t3lib_div::devLog('Active login (eg. with login form)', 't3lib_userAuth');

/*
				// check referer for submitted login values
			if ($this->formfield_status && $loginData['uident'] && $loginData['uname'])	{
				$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
				if (!$this->getMethodEnabled && ($httpHost!=$authInfo['refInfo']['host'] && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']))	{
					die('Error: This host address ("'.$httpHost.'") and the referer host ("'.$authInfo['refInfo']['host'].'") mismatches!<br />
						It\'s possible that the environment variable HTTP_REFERER is not passed to the script because of a proxy.<br />
						The site administrator can disable this check in the "All Configuration" section of the Install Tool (flag: TYPO3_CONF_VARS[SYS][doNotCheckReferer]).');
				}

					// delete old user session if any
				$this->logoff();
			}
*/

				// Refuse login for _CLI users (used by commandline scripts)
			if ((strtoupper(substr($loginData['uname'],0,5))=='_CLI_') && (!defined('TYPO3_cliMode') || !TYPO3_cliMode))	{	// although TYPO3_cliMode should never be set when using active login...
				die('Error: You have tried to login using a CLI user. Access prohibited!');
			}
		}


		// the following code makes auto-login possible (if configured). No submitted data needed

			// re-read user session
		$authInfo['userSession'] = $this->fetchUserSession();
		$haveSession = is_array($authInfo['userSession']) ? TRUE : FALSE;

		if ($this->writeDevLog)	{
			if ($haveSession)	{
				t3lib_div::devLog('User session found: '.t3lib_div::arrayToLogString($authInfo['userSession'], array($this->userid_column,$this->username_column)), 't3lib_userAuth', 0);
			}
			if (is_array($this->svConfig['setup']))	{
				t3lib_div::devLog('SV setup: '.t3lib_div::arrayToLogString($this->svConfig['setup']), 't3lib_userAuth', 0);
			}
		}

			// fetch user if ...
		if ($activeLogin
				|| (!$haveSession && $this->svConfig['setup'][$this->loginType.'_fetchUserIfNoSession'])
				|| $this->svConfig['setup'][$this->loginType.'_alwaysFetchUser']) {

				// use 'auth' service to find the user
				// first found user will be used
			$serviceChain = '';
			$subType = 'getUser'.$this->loginType;
			while (is_object($serviceObj = t3lib_div::makeInstanceService('auth', $subType, $serviceChain))) {
				$serviceChain.=','.$serviceObj->getServiceKey();
				$serviceObj->initAuth($subType, $loginData, $authInfo, $this);
				if ($row=$serviceObj->getUser()) {
					$tempuserArr[] = $row;

					if ($this->writeDevLog) 	t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($row, array($this->userid_column,$this->username_column)), 't3lib_userAuth', 0);

						// user found, just stop to search for more if not configured to go on
					if(!$this->svConfig['setup'][$this->loginType.'_fetchAllUsers']) {
						break;
					}
				}
				unset($serviceObj);
			}
			unset($serviceObj);

			if ($this->writeDevLog && $this->svConfig['setup'][$this->loginType.'_alwaysFetchUser']) 	t3lib_div::devLog($this->loginType.'_alwaysFetchUser option is enabled', 't3lib_userAuth');
			if ($this->writeDevLog && $serviceChain) 	t3lib_div::devLog($subType.' auth services called: '.$serviceChain, 't3lib_userAuth');
			if ($this->writeDevLog && !count($tempuserArr)) 	t3lib_div::devLog('No user found by services', 't3lib_userAuth');
			if ($this->writeDevLog && count($tempuserArr)) 	t3lib_div::devLog(count($tempuserArr).' user records found by services', 't3lib_userAuth');
		}


			// If no new user was set we use the already found user session
		if (!count($tempuserArr) && $haveSession)	{
			$tempuserArr[] = $authInfo['userSession'];
			$tempuser = $authInfo['userSession'];
				// User is authenticated because we found a user session
			$authenticated = TRUE;

			if ($this->writeDevLog) 	t3lib_div::devLog('User session used: '.t3lib_div::arrayToLogString($authInfo['userSession'], array($this->userid_column,$this->username_column)), 't3lib_userAuth');
		}


			// Re-auth user when 'auth'-service option is set
		if ($this->svConfig['setup'][$this->loginType.'_alwaysAuthUser']) {
			$authenticated = FALSE;
			if ($this->writeDevLog) 	t3lib_div::devLog('alwaysAuthUser option is enabled', 't3lib_userAuth');
		}


			// Authenticate the user if needed
		if (count($tempuserArr) && !$authenticated)	{

			foreach ($tempuserArr as $tempuser)	{

				// use 'auth' service to authenticate the user
				// if one service returns FALSE then authentication failed
				// a service might return 100 which means there's no reason to stop but the user can't be authenticated by that service

				if ($this->writeDevLog) 	t3lib_div::devLog('Auth user: '.t3lib_div::arrayToLogString($tempuser), 't3lib_userAuth');

				$serviceChain='';
				$subType = 'authUser'.$this->loginType;
				while (is_object($serviceObj = t3lib_div::makeInstanceService('auth', $subType, $serviceChain))) {
//t3lib_div::debug('Chain: '.$serviceChain);
					$serviceChain.=','.$serviceObj->getServiceKey();
					$serviceObj->initAuth($subType, $loginData, $authInfo, $this);
					if (($ret=$serviceObj->authUser($tempuser)) > 0) {
//$check = $subType.'('.$ret.')';
//t3lib_div::debug($check);

							// if the service returns >=200 then no more checking is needed - useful for IP checking without password
						if (intval($ret) >= 200)	{
							$authenticated = TRUE;
							break;
						} elseif (intval($ret) >= 100) {
							// Just go on. User is still not authenticated but there's no reason to stop now.
						} else {
							$authenticated = TRUE;
						}

					} else {
						$authenticated = FALSE;
						break;
					}
					unset($serviceObj);
				}
				unset($serviceObj);

				if ($this->writeDevLog && $serviceChain) 	t3lib_div::devLog($subType.' auth services called: '.$serviceChain, 't3lib_userAuth');

				if ($authenticated) {
						// leave foreach() because a user is authenticated
					break;
				}
			}
		}

			// If user is authenticated a valid user is in $tempuser
		if ($authenticated)	{
				// reset failure flag
			$this->loginFailure = FALSE;


				// Insert session record if needed:
			if (!($haveSession && (
				$tempuser['ses_id']==$this->id || 	// check if the tempuser has the current session id
				$tempuser['uid']==$authInfo['userSession']['ses_userid'] 	// check if the tempuser has the uid of the fetched session user
				))) {
				$this->createUserSession($tempuser);

					// The login session is started.
				$this->loginSessionStarted = TRUE;
			}

				// User logged in - write that to the log!
			if ($this->writeStdLog && $activeLogin) {
				$this->writelog(255,1,0,1,
					'User %s logged in from %s (%s)',
					Array($tempuser[$this->username_column], t3lib_div::getIndpEnv('REMOTE_ADDR'), t3lib_div::getIndpEnv('REMOTE_HOST')),
					'','','',-1,'',$tempuser['uid']
				);
			}

			if ($this->writeDevLog && $activeLogin) 	t3lib_div::devLog('User '.$tempuser[$this->username_column].' logged in from '.t3lib_div::getIndpEnv('REMOTE_ADDR').' ('.t3lib_div::getIndpEnv('REMOTE_HOST').')', 't3lib_userAuth', -1);
			if ($this->writeDevLog && !$activeLogin) 	t3lib_div::devLog('User '.$tempuser[$this->username_column].' authenticated from '.t3lib_div::getIndpEnv('REMOTE_ADDR').' ('.t3lib_div::getIndpEnv('REMOTE_HOST').')', 't3lib_userAuth', -1);

			if($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] == 3 && $this->user_table == 'be_users')	{
				$requestStr = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT'), strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir));
				$backendScript = t3lib_BEfunc::getBackendScript();
				if($requestStr == $backendScript && t3lib_div::getIndpEnv('TYPO3_SSL'))	{
					list(,$url) = explode('://',t3lib_div::getIndpEnv('TYPO3_SITE_URL'),2);
					list($server,$address) = explode('/',$url,2);
					if (intval($TYPO3_CONF_VARS['BE']['lockSSLPort'])) {
						$sslPortSuffix = ':'.intval($TYPO3_CONF_VARS['BE']['lockSSLPort']);
						$server = str_replace($sslPortSuffix,'',$server);	// strip port from server
					}
					header('Location: http://'.$server.'/'.$address.TYPO3_mainDir.$backendScript);
					exit;
				}
			}

		} elseif ($activeLogin || count($tempuserArr)) {
			$this->loginFailure = TRUE;

			if ($this->writeDevLog && !count($tempuserArr) && $activeLogin) 	t3lib_div::devLog('Login failed: '.t3lib_div::arrayToLogString($loginData), 't3lib_userAuth', 2);
			if ($this->writeDevLog && count($tempuserArr)) 	t3lib_div::devLog('Login failed: '.t3lib_div::arrayToLogString($tempuser, array($this->userid_column,$this->username_column)), 't3lib_userAuth', 2);
		}


			// If there were a login failure, check to see if a warning email should be sent:
		if ($this->loginFailure && $activeLogin)	{
			if ($this->writeDevLog) 	t3lib_div::devLog('Call checkLogFailures: '.t3lib_div::arrayToLogString(array('warningEmail'=>$this->warningEmail,'warningPeriod'=>$this->warningPeriod,'warningMax'=>$this->warningMax,)), 't3lib_userAuth', -1);

			$this->checkLogFailures($this->warningEmail, $this->warningPeriod, $this->warningMax);
		}
	}

	/**
	 * Overload t3lib_userauth::createUserSession()
	 * In the case of remote calls, we don't want to create sessions, so we just return the tempuser array as is
	 */
	function createUserSession($tempuser) {
		$this->user = $tempuser;
	}

	/**
	 * Overload t3lib_userauth::getLoginFormData()
	 * We want to use that method, but also force the status to be "login" because it is the only expected status
	 */
	function getLoginFormData() {
		$loginData = parent::getLoginFormData();
		$loginData['status'] = 'login';
		return $loginData;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/remote_server/class.tx_remoteserver_beuserauth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/remote_server/class.tx_remoteserver_beuserauth.php']);
}
?>