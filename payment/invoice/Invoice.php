<?php

if(empty($GLOBALS['SysValue'])) exit(header("Location: /"));

require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";
require "InvoiceSDK/GET_TERMINAL.php";
require "InvoiceSDK/common/ITEM.php";

use PHPShopCart;

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
        $request->description = "PHPShop module";
        $request->defaultPrice = "10";

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

        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($amount, $id);
        $request->settings = $this->getSettings($terminal);
        $request->receipt = $this->getReceipt();

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

    /**
     * @return INVOICE_ORDER
     */

    private function getOrder($amount, $id) {
        $order = new INVOICE_ORDER();
        $order->amount = $amount;
        $order->id = "$id" . "-" . bin2hex(random_bytes(5));
        $order->currency = "RUB";

        return $order;
    }

    /**
     * @return INVOICE_SETTINGS
     */

    private function getSettings($terminal) {
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $settings = new INVOICE_SETTINGS();
        $settings->terminal_id = $terminal;
        $settings->success_url = $url;
        $settings->fail_url = $url;

        return $settings;
    }

    /**
     * @return ITEM
     */

    private function getReceipt() {
        $receipt = array();
        $order = new PHPShopCart();
        $basket = $order->getArray();

        foreach ($basket as $basketItem) {
            $item = new ITEM();
            $item->name = $basketItem['name'];
            $item->price = $basketItem['price'];
            $item->resultPrice = $basketItem['total'];
            $item->quantity = $basketItem['num'];

            array_push($receipt, $item);
        }

        return $receipt;
    }

    public function callback($notification, $SysValue) {
        $type = $notification["notification_type"];
        $id = strstr($notification["order"]["id"], "-", true);
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
        $terminal = new GET_TERMINAL();
        $terminal->alias = file_get_contents("invoice_tid");
        $info = $this->restClient->GetTerminal($terminal);

        if($info->id == null || $info->id != $terminal->alias){
            return null;
        } else {
            return $info->id;
        }
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