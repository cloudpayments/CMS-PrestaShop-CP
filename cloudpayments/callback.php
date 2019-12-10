<?php
/**
* Process callbacks
*/
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/Callback.php');

$callback = new CloudpaymentsCallback(Tools::getValue('callback_type'));

echo $callback->getResponse();