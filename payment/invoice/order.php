<?php
if(empty($GLOBALS['SysValue'])) exit(header("Location: /"));

$api_key = $SysValue['invoice']['api_key'];
$login = $SysValue['invoice']['login'];

require_once "Invoice.php";

$amount = number_format($GLOBALS['SysValue']['other']['total'], 2, '.', '');
$id = $_POST['ouid'];

$invoice = new Invoice($login, $api_key);
$payment_url = $invoice->createPayment($amount, $id);

$disp = header('Location: '.$payment_url);

if($payment_url == "/" || $payment_url == null) {
    $disp = '
    <div align="center">

Error when creating the terminal

</div>
    ';
}
