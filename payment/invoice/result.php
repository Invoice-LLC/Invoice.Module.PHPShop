<?php

$SysValue = parse_ini_file("../../phpshop/inc/config.ini", 1);

require_once "Invoice.php";

$api_key = $SysValue['invoice']['api_key'];
$login = $SysValue['invoice']['login'];
$postData = file_get_contents('php://input');
$notification = json_decode($postData, true);

$invoice = new Invoice($login, $api_key);
$invoice->callback($notification, $SysValue);