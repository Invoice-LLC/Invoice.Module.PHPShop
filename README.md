<h1>Invoice PHP Shop plugin</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.PHPShop/archive/master.zip) и скопируйте содержимое архива в корень сайта
2. В файле %корень сайта%/phpshop/inc/config.php,  добавьте строчки
```
[invoice]
api_key = "Ваш API Key";
login = "Ваш Id компании";
```
<br>Api ключи и Merchant Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>

3. Перейдите во вкладку **Заказы->Способы оплаты->Добавить способ оплаты** и заполните как показано ниже
![Imgur](https://imgur.com/Nuam3R6.png)
4. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
   с типом **WebHook** и адресом: **%URL сайта%/payment/invoice/result.php**
   ![Imgur](https://imgur.com/LZEozhf.png)
