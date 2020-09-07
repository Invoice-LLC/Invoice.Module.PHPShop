<h1>Invoice PHP Shop plugin</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.PHPShop/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. В файле %корень сайта%/phpshop/inc/config.php,  добавьте строчки
```
[invoice]
api_key = "Ваш API Key";
login = "Ваш логин от личного кабинета";
```
3. Перейдите во вкладку **Заказы->Способы оплаты->Добавить способ оплаты** и заполните как показано ниже
![Imgur](https://imgur.com/Nuam3R6.png)
4. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
   с типом **WebHook** и адресом: **%URL сайта%/payment/invoice/result.php**
   ![Imgur](https://imgur.com/LZEozhf.png)
