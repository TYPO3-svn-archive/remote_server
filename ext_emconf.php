<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "remote_server".
 *
 * Auto generated 28-03-2013 14:39
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Remote Server',
	'description' => 'Enable TYPO3 to act as a remote application server for web services calls. BE access restrictions apply. Not maintained anymore.',
	'category' => 'be',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'obsolete',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.2.1',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.2.0-4.5.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:9:"ChangeLog";s:4:"d2c4";s:36:"class.tx_remoteserver_beuserauth.php";s:4:"a075";s:30:"class.tx_remoteserver_helo.php";s:4:"f472";s:34:"class.tx_remoteserver_response.php";s:4:"ce7b";s:8:"conf.php";s:4:"be6a";s:21:"ext_conf_template.txt";s:4:"ac4b";s:12:"ext_icon.gif";s:4:"e0f4";s:17:"ext_localconf.php";s:4:"5d28";s:9:"index.php";s:4:"125e";s:8:"init.php";s:4:"f77d";s:8:"json.php";s:4:"e081";s:10:"README.txt";s:4:"edf1";s:14:"doc/manual.sxw";s:4:"d511";s:18:"samples/caller.php";s:4:"26fa";}',
	'suggests' => array(
	),
);

?>