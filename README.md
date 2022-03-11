Zippy Store
========
Облікова  програма (склад, торгівля, виробництво) з веб-інтерфейсом.  
Призначена для використання малим бізнесом зі спрощеною формою обліку, який не використовує повноцінного бухгалтерського обліку.
   
Домашня сторінка:  [https://zippy.com.ua/zstore](https://zippy.com.ua/zstore)  

####
Основна функціональність
 
* управління складами
* закупівля
* продаж
* облік курсу валют при закупівлі  
* облік платежів та взаєморозрахунки з котрагентами
* партійний облік та облік за серіями виробника
* управління користувачами та доступом, особистий кабінет користувача
* робота з лідами та інші елементи CRM
* звіти з продажу, закупівлі, руху товару
* послуги, завдання, календар виконання робіт
* облік обладнання
* API для обміну з іншими інформаційними системами, наприклад інтернет-магазином, написаному на іншій платформі.
* підтримка сканера (клавіатурного) штрих-коду .
* підтримка принтерів чеків та етикеток.
* розділення доступу між філією (наприклад торговими точками)
* модуль інтеграції з Опенкарт
* модуль інтеграції з Woocomerce
* інтеграція з Новою Поштою  
* програмний РРО  
* інтеграція з сервісами СМС розсилок
* модуль для громадського харчування
* розрахунок зарплати
* виробництво


Вимоги: PHP7.4+    Mysql 5.7+ 


Налаштування  системи.
--------------------

  Створити БД (кодування utf8_general_ci), виконати SQL скрипти (папка DB) спочатку структуру db.sql потім дані ініціалізації initdata.sql  .
  
  Зкопіювати вміст папки www у кореневий каталог сайту.  
  Виконати composer.json для завантаження сторонніх бібліотек.
  
  Конфігураційні файли лежать у папці config.

  Встановити параметри з'єднання з базою даних у файлі config.ini. Також можна задати налаштування поштового серверу,
  мову програми, увімкнути необхідні модулі.  
  Також необхідно переконатися, що дозволено право запису в папки uploads та logs.  
  Залогінитись дефолтним користувачем admin admin  
  Змінити дефолтний пароль у профілі  