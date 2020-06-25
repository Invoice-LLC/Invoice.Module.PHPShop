<?php

if(empty($GLOBALS['SysValue'])) exit(header("Location: /"));

require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";

class Invoice
{
    private $restClient;

    public function __construct($login, $api_key)
    {
        $this->restClient = new RestClient($login, $api_key);
    }

    public function createTerminal() {
        $request = new CREATE_TERMINAL("PHPShop");
        $request->type = "dynamical";

        $this->log(json_encode($request));
        $info = $this->restClient->CreateTerminal($request);
        $this->log(json_encode($info));

        if($info == null or $info->error != null) {
            return false;
        } else {
            $this->saveTerminal($info->id);
            return true;
        }
    }

    public function checkOrCreateTerminal() {
        $id = $this->getTerminal();
        if($id == null or empty($id)) {
            $this->createTerminal();
        }
    }

    public function createPayment($amount, $id) {
        $this->checkOrCreateTerminal();
        $terminal = $this->getTerminal();

        $order = new INVOICE_ORDER($amount);
        $order->id = $id;
        $settings = new SETTINGS($terminal);
        $settings->success_url = ( ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

        $request = new CREATE_PAYMENT($order, $settings, null);

        $this->log(json_encode($request));
        $info = $this->restClient->CreatePayment($request);
        $this->log(json_encode($info));
        if($info == null or $info->error != null) {
            $this->sendAlert("Не удалось создать платеж");
            return "/";
        } else {
            return $info->payment_url;
        }
    }

    public function callback($notification, $SysValue) {
        $type = $notification["notification_type"];
        $id = $notification["order"]["id"];

        $signature = $notification["signature"];

        if($signature != $this->getSignature($notification["id"], $notification["status"], $this->restClient->apiKey)) {
            $this->log("Wrong signature");
            return "Wrong signature";
        }

        if($type == "pay") {

            if($notification["status"] == "successful") {
                $this->setPaymentStatus(true,$id, $notification["order"]["amount"], $SysValue);
                return "payment successful";
            }
            if($notification["status"] == "error") {
                $this->setPaymentStatus(false,$id, $notification["order"]["amount"], $SysValue);
                return "payment failed";
            }
        }

        $this->log("Wrong type");
        return "null";
    }

    public function getSignature($id, $status, $key) {
        return md5($id.$status.$key);
    }


    public function setPaymentStatus($paid, $id, $amount, $SysValue) {
        if(!$paid) return;
        $this->log("Result");

        $link_db = mysqli_connect($SysValue['connect']['host'], $SysValue['connect']['user_db'], $SysValue['connect']['pass_db']);
        mysqli_select_db($link_db,$SysValue['connect']['dbase']);

        $sql = "select sum from " . $SysValue['base']['table_name1'] . " where uid=\"" . mysqli_real_escape_string($link_db, $id) . "\" limit 1";
        $r = mysqli_query($link_db,$sql);
        $num = @mysqli_num_rows($r);

        if(empty($num)) {
            echo "Not found";
            $this->log("Order not found");
            return;
        }


        $arr = explode("-", $id);
        $inv_id = $arr[0]."".$arr[1];

        $sql = "INSERT INTO " . $SysValue['base']['table_name33'] . " VALUES
            ($inv_id,'Invoice','{$amount}','" . date("U") . "')";
        $r = mysqli_query($link_db,$sql);
    }

    public function saveTerminal($id) {
        file_put_contents("invoice_tid", $id);
    }

    public function getTerminal() {
        return file_get_contents("invoice_tid");
    }

    public function sendAlert($msg) {
        echo "<script type='text/javascript'> alert('$msg'); </script>";
    }

    public function log($log) {
        $fp = fopen('invoice_payment.log', 'a+');
        fwrite($fp, "\n".$log);
        fclose($fp);
    }
}