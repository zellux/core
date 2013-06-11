<?php

require_once('apps/files_encryption/appinfo/app.php');

//$status = OC_App::isEnabled('files_encryption');
//OC_App::enable('files_encryption');

$util = new OCA\Encryption\Util(new OC_Filesystemview('/'), OC_User::getUser());
$util->decryptAll();

//if ($status === false) {
//	OC_App::disable('files_encryption');
//}


\OCP\JSON::success(array('data' => array('message' => 'looks good')));

