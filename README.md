ZStore
========
Облікова  програма (склад, торгівля, виробництво) з веб-інтерфейсом.  
Призначена для автоматизації малого бізнесу зі спрощеною формою обліку, який не використовує 
повноцінний бухгалтерський облік.
   
Домашня сторінка:  [https://zippy.com.ua/zstore](https://zippy.com.ua/zstore)    
 
####
Основна функціональність
 
* управління складами
* закупівля
* продаж
* облік платежів та взаєморозрахунки з контрагентами
* партійний облік та облік за серіями виробника
* управління користувачами та доступом, особистий кабінет користувача
* робота з лідами та інші елементи CRM
* звіти з продажу, закупівлі, руху товару
* послуги, завдання, календар виконання робіт
* виробництво, наряди, виробничі процеси та  виробничі етапи
* розрахунок зарплати
* підтримка сканера (клавіатурного) штрих-коду  
* підтримка принтерів чеків та етикеток 
* розділення доступу між філіями (наприклад торговими точками)
* модуль інтеграції з Опенкарт
* модуль інтеграції з Woocomerce
* інтеграція з Новою Поштою  
* інтеграція з CheckBox  
* вбудований програмний РРО  
* інтеграція з сервісами СМС розсилок
* API для обміну з іншими інформаційними системами, наприклад інтернет-магазином, написаному на іншій платформі.
* модуль для громадського харчування
* вбудований інтернет-магазин




Вимоги: PHP7.4+    Mysql 5.7+   (можлива  робота  з  PostgerSQL)


Налаштування  системи.
--------------------

  Створити БД (кодування utf8_general_ci), виконати SQL скрипти (папка DB) спочатку структуру db.sql потім дані ініціалізації initdata.sql  .
  
  Зкопіювати вміст папки www у кореневий каталог сайту.  
  Виконати composer.json для завантаження сторонніх бібліотек (або зкачати [готову збірку](https://zippy.com.ua/download/fullzstore.zip) ).
  
  Конфігураційні файли лежать у папці config.

  Встановити параметри з'єднання з базою даних у файлі config.php та задати налаштування поштового серверу.
  В загальних налаштуваннях задати  основнi параметри робочого оточення та увімкнути необхідні модулі .  
  Також необхідно переконатися, що дозволено право запису в папки uploads та logs.  
  Залогінитись дефолтним користувачем admin admin  
  Змінити дефолтний пароль у профілі. Задати персональнi налаштування профiлю.  