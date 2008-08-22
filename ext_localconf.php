<?php
// Register HELO test method for Remote Server

$TYPO3_CONF_VARS['EXTCONF']['remoteserver']['services']['remote_server::helo'] = 'typo3conf/ext/remote_server/class.tx_remoteserver_helo.php:tx_remoteserver_helo->helo';
?>