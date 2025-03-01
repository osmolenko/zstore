<?php

namespace App;

use App\Entity\User;
use ZCL\DB\DB as DB;

/**
 * Вспомагательный  класс  для  работы  с  бизнес-данными
 */
class Helper
{
    public const STAT_HIT_SHOP           = 1;     //посещение  онлайн  каталога
    public const STAT_ORDER_SHOP         = 2;     //заказы  в  онлайн каталоге
    public const STAT_VIEW_ITEM          = 3;     //перегляд  товару


    private static $meta = array(); //кеширует метаданные

    /**
     * Выполняет  логин  в  системму
     *
     * @param mixed $login
     * @param mixed $password
     * @return  boolean
     */
    public static function login($login, $password = null) {

        $user = User::getFirst("  userlogin=  " . User::qstr($login));

        if ($user == null) {
            return false;
        }

        if ($user->disabled == 1) {
            return false;
        }


        if ($user->userpass == $password) {
            return $user;
        }
        if (strlen($password) > 0) {
            $b = password_verify($password, $user->userpass);
            return $b ? $user : false;
        }
        return false;
    }

    /**
     * Проверка  существования логина
     *
     * @param mixed $login
     */
    public static function existsLogin($login) {
        $list = User::find("  userlogin= " . User::qstr($login));

        return count($list) > 0;
    }

    public static function logout() {

        System::clean() ;
        System::getSession()->clean();

        setcookie("remember", '', 0);
        System::setUser(new \App\Entity\User());
        $_SESSION['user_id'] = 0;
        $_SESSION['userlogin'] = 'Гость';

        Application::Redirect("\\App\\Pages\\UserLogin");


    }

    public static function generateMenu($meta_type) {

        $conn = \ZDB\DB::getConnect();
        $rows = $conn->Execute("select *  from metadata where meta_type= {$meta_type} and disabled <> 1 order  by  description ");
        $menu = array();
        $groups = array();
        $user = System::getUser();
        $arraymenu = array("groups" => array(), "items" => array());

        $aclview = explode(',', $user->aclview);
        foreach ($rows as $meta_object) {
            $meta_id = $meta_object['meta_id'];

            if (!in_array($meta_id, $aclview) && $user->rolename != 'admins') {
                continue;
            }

            if (strlen($meta_object['menugroup']) == 0) {
                $menu[$meta_id] = $meta_object;
            } else {
                if (!isset($groups[$meta_object['menugroup']])) {
                    $groups[$meta_object['menugroup']] = array();
                }
                $groups[$meta_object['menugroup']][$meta_id] = $meta_object;
            }
        }
        switch($meta_type) {
            case 1:
                $dir = "Pages/Doc";
                break;
            case 2:
                $dir = "Pages/Report";
                break;
            case 3:
                $dir = "Pages/Register";
                break;
            case 4:
                $dir = "Pages/Reference";
                break;
            case 5:
                $dir = "Pages/Service";
                break;
        }


        foreach ($menu as $item) {

            $arraymenu['items'][] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
        }
        $i = 1;
        foreach ($groups as $gname => $group) {

            $items = array();

            foreach ($group as $item) {

                $items[] = array('name' => $item['description'], 'link' => "/index.php?p=App/{$dir}/{$item['meta_name']}");
            }


            $arraymenu['groups'][] = array('grname' => $gname, 'items' => $items);
        }

        return $arraymenu;
    }

    public static function generateSmartMenu() {

        $conn = \ZDB\DB::getConnect();
        $user = System::getUser();
        $smartmenu = $user->smartmenu;

        if (strlen($smartmenu) == 0) {
            return "";
        }

        $rows = $conn->Execute("select *  from  metadata  where disabled <> 1 and  meta_id in ({$smartmenu})   ");

        $textmenu = "";
        $aclview = explode(',', $user->aclview);

        foreach ($rows as $item) {

            if (!in_array($item['meta_id'], $aclview) && $user->rolename != 'admins') {
                continue;
            }
            $icon = '';

            switch((int)$item['meta_type']) {
                case 1:
                    $dir = "Pages/Doc";
                    $icon = "<i class=\"nav-icon fa fa-file\"></i>";
                    break;
                case 2:
                    $dir = "Pages/Report";
                    $icon = "<i class=\"nav-icon fa fa-chart-bar\"></i>";
                    break;
                case 3:
                    $dir = "Pages/Register";
                    $icon = "<i class=\"nav-icon fa fa-list\"></i>";
                    break;
                case 4:
                    $dir = "Pages/Reference";
                    $icon = "<i class=\"nav-icon fa fa-book\"></i>";
                    break;
                case 5:
                    $dir = "Pages/Service";
                    $icon = "<i class=\"nav-icon fas fa-project-diagram\"></i>";
                    break;
            }

            $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mb-1  \" href=\"/index.php?p=App/{$dir}/{$item['meta_name']}\">{$icon} {$item['description']}</a> ";
        }
        $role = \App\Entity\UserRole::load($user->role_id);

        $mod = self::modulesMetaData($role);
        $smartmenu = explode(',', $smartmenu);
        foreach ($mod as $p) {
            if (in_array($p->meta_id, $smartmenu)) {
                $textmenu .= " <a class=\"btn btn-sm btn-outline-primary mb-1 mr-2\" href=\"/index.php?p=App/Modules{$p->meta_name}\">  <i class=\"nav-icon fa fa-puzzle-piece\"></i> {$p->description}</a> ";
            }
        }
        return $textmenu;
    }

    //метаданные   модулей
    public static function modulesMetaData($role) {

        $modules = \App\System::getOptions("modules");

        $mdata = array();
        if(($modules['note'] ?? 0)== 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'note') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10000, 'meta_name' => "/Note/Pages/Main", 'meta_type' => 6, 'description' => "База знань"));
            }
        }


        if(($modules['shop'] ?? 0)== 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'shop') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10002, 'meta_name' => "/Shop/Pages/Admin/ProductList", 'meta_type' => 6, 'description' => "Товари в онлайн каталозі" ));
            }
        }




        if(($modules['wc'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10009, 'meta_name' => "/WC/Orders", 'meta_type' => 6, 'description' => "Замовлення (WC)" ));
            }
        }
        if(($modules['wc']?? 0)  == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'wc') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10010, 'meta_name' => "/WC/Items", 'meta_type' => 6, 'description' => "Товари (WC)"));
            }
        }

        if(($modules['promua'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'promua') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10015, 'meta_name' => "/PU/Orders", 'meta_type' => 6, 'description' => "Замовлення (PU)"  ));
            }
        }

        if(($modules['issue'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'issue') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10014, 'meta_name' => "/Issue/Pages/IssueList", 'meta_type' => 6, 'description' => "Завдання (Проекти)"));
            }
        }
        if(($modules['issue'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'issue') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10017, 'meta_name' => "/Issue/Pages/ProjectList", 'meta_type' => 6, 'description' =>   "Проекти"  , ));
            }
        }

        if(($modules['ocstore'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10005, 'meta_name' => "/OCStore/Orders", 'meta_type' => 6, 'description' => "Замовлення (Опенкарт)" ));
            }
        }
        if(($modules['ocstore'] ?? 0) == 1) {
            if ($role->rolename == 'admins' || strpos($role->modules, 'ocstore') !== false) {
                $mdata[] = new \App\Entity\MetaData(array('meta_id' => 10018, 'meta_name' => "/OCStore/Items", 'meta_type' => 6, 'description' => "Товари (Опенкарт)"));
            }
        }
        return $mdata;
    }

    public static function loadEmail($template, $keys = array()) {
        global $logger;

        $templatepath = _ROOT . 'templates/email/' . $template . '.tpl';
        if (file_exists($templatepath) == false) {

            $logger->error($templatepath . " is wrong");
            return "";
        }

        $template = @file_get_contents($templatepath);

        $m = new \Mustache_Engine();
        $template = $m->render($template, $keys);

        return $template;
    }

    public static function sendLetter($emailto, $text, $subject = "") {
        global $_config;

        $emailfrom = $_config['smtp']['emailfrom'];
        if(strlen($emailfrom)==0) {
            $emailfrom = $_config['smtp']['user'];

        }

        try {

            $mail = new \PHPMailer\PHPMailer\PHPMailer();

            if ($_config['smtp']['usesmtp'] == true) {
                $mail->isSMTP();
                $mail->Host = $_config['smtp']['host'];
                $mail->Port = $_config['smtp']['port'];
                $mail->Username = $_config['smtp']['user'];
                $mail->Password = $_config['smtp']['pass'];
                $mail->SMTPAuth = true;
                if ($_config['smtp']['tls'] == true) {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            }


            $mail->setFrom($emailfrom);
            $mail->addAddress($emailto);
            $mail->Subject = $subject;
            $mail->msgHTML($text);
            $mail->CharSet = "UTF-8";
            $mail->IsHTML(true);
            //  $d = $mail->send() ;
            if ($mail->send() === false) {
                System::setErrorMsg($mail->ErrorInfo);
            } else {
                //  System::setSuccessMsg('E-mail відправлено');
            }
        } catch(\Exception $e) {
            System::setErrorMsg($e->getMessage());

        }

        /*
          $from_name = '=?utf-8?B?' . base64_encode("Онлайн каталог") . '?=';
          $subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
          mail(
          $emailto,
          $subject,
          $text,
          "From: " . $from_name." <{$_config['smtp']['emailfrom']}>\r\n".
          "Content-type: text/html; charset=\"utf-8\""
          );
         */
    }

    /**
     * Запись  файла   в БД
     *
     * @param mixed $file
     * @param mixed $itemid ID  объекта
     * @param mixed $itemtype тип  объекта (документ - 0 )
     */
    public static function addFile($file, $itemid, $comment, $itemtype = 0) {
        $conn = DB::getConnect();
        $filename = $file['name'];
        $imagedata = getimagesize($file["tmp_name"]);
        $mime = is_array($imagedata) ? $imagedata['mime'] : "";

        if (strpos($filename, '.pdf') > 0) {
            $mime = "application/pdf";
        }

        $comment = $conn->qstr($comment);
        $filename = $conn->qstr($filename);
        $sql = "insert  into files (item_id,filename,description,item_type,mime) values ({$itemid},{$filename},{$comment},{$itemtype},'{$mime}') ";
        $conn->Execute($sql);
        $id = $conn->Insert_ID();

        $data = file_get_contents($file['tmp_name']);


        if($conn->dataProvider=='postgres') {
            $data = pg_escape_bytea($data);

        }
        $data = $conn->qstr($data);
        $sql = "insert  into filesdata (file_id,filedata) values ({$id},{$data}) ";
        $conn->Execute($sql);
        return $id;
    }

    /**
     * список  файдов  пррепленных  к  объекту
     *
     * @param mixed $item_id
     * @param mixed $item_type
     */
    public static function getFileList($item_id, $item_type = 0) {
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute("select * from files where item_id={$item_id} and item_type={$item_type} ");
        $list = array();
        foreach ($rs as $row) {
            $item = new \App\DataItem();
            $item->file_id = $row['file_id'];
            $item->filename = $row['filename'];
            $item->description = $row['description'];
            $item->mime = $row['mime'];

            $list[] = $item;
        }

        return $list;
    }

    /**
     * удаление  файла
     *
     * @param mixed $file_id
     */
    public static function deleteFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  files  where  file_id={$file_id}");
        $conn->Execute("delete  from  filesdata  where  file_id={$file_id}");
    }

    /**
     * Возвращает  файл  и  его  содержимое
     *
     * @param mixed $file_id
     */
    public static function loadFile($file_id) {
        $conn = \ZDB\DB::getConnect();
        $rs = $conn->Execute("select filename,filedata,mime from files join filesdata on files.file_id = filesdata.file_id  where files.file_id={$file_id}  ");
        foreach ($rs as $row) {
            return $row;
        }

        return null;
    }

    /**
     * возварщает список  документов
     *
     * @param mixed $id
     */
    public static function getDocTypes() {
        $conn = \ZDB\DB::getConnect();
        $groups = array();

        $rs = $conn->Execute('SELECT description,meta_id FROM   metadata where meta_type = 1 order by description');
        foreach ($rs as $row) {
            $groups[$row['meta_id']] = $row['description'];
        }
        return $groups;
    }

    /**
     * возварщает запись  метаданных
     *
     * @param mixed $id
     */
    public static function getMetaType($id) {
        if (is_array(self::$meta[$id] ?? null) == false) {
            $conn = DB::getConnect();
            $sql = "select * from   metadata where meta_id = " . $id;
            self::$meta[$id] = $conn->GetRow($sql);
        }

        return self::$meta[$id];
    }

    /**
     * логгирование
     *
     * @param mixed $msg
     */
    public static function log($msg) {
        global $logger;
        $logger->debug($msg);
    }

    /**
     * логгирование    ошибок
     *
     * @param mixed $msg
     */
    public static function logerror($msg) {
        global $logger;
        $logger->error($msg);
    }

    /**
     * Возвращает компанию  по  умолчанию
     *
     */
    public static function getDefFirm() {
        $user = System::getUser();
        if ($user->deffirm > 0) {
            return $user->deffirm;
        }
        $st = \App\Entity\Firm::getList();

        if (count($st) > 0) {
            $keys = array_keys($st);
            return $keys[count($keys)-1];
        }
        return 0;
    }

    /**
     * Возвращает склад  по  умолчанию
     *
     */
    public static function getDefStore() {
        $user = System::getUser();
        if ($user->defstore > 0) {
            return $user->defstore;
        }
        $st = \App\Entity\Store::getList();
        if (count($st) > 0) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
    * Возвращает расчетный счет  по  умолчанию
    *
    */
    public static function getDefMF() {
        $user = System::getUser();
        if ($user->defmf > 0) {
            return $user->defmf;
        }

        $st = \App\Entity\MoneyFund::getList();
        if (count($st) > 0) {
            $keys = array_keys($st);
            return $keys[0];
        }
        return 0;
    }

    /**
     * источники  продаж
     *
     */
    public static function getSaleSources() {
        $common = System::getOptions("common");
        if (!is_array($common)) {
            $common = array();
        }
        $salesourceslist = $common['salesources'];
        if (is_array($salesourceslist) == false) {
            $salesourceslist = array();
        }
        $slist = array();
        foreach ($salesourceslist as $s) {
            $slist[$s->id] = $s->name;
        }
        return $slist;
    }

    /**
     * Возвращает источник продаж  по  умолчанию
     *
     */
    public static function getDefSaleSource() {
        $user = System::getUser();
        if ($user->defsalesource > 0) {
            return $user->defsalesource;
        }

        $slist = Helper::getSaleSources();

        if (count($slist) > 0) {
            $keys = array_keys($slist);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Возвращает первый тип  цен  как  по  умолчанию
     *
     */
    public static function getDefPriceType() {

        $pt = \App\Entity\Item::getPriceTypeList();
        if (count($pt) > 0) {
            $keys = array_keys($pt);
            return $keys[0];
        }
        return 0;
    }

    /**
     * Форматирование количества
     *
     * @param mixed $qty
     * @return mixed
     */
    public static function fqty($qty) {
        if (strlen($qty) == 0) {
            return '';
        }
        if( is_numeric($qty) &&  abs($qty)<0.0005) {
            $qty  =0;
        }
        $qty = str_replace(',', '.', $qty);
        $qty = preg_replace("/[^0-9\.\-]/", "", $qty);
     
        $common = System::getOptions("common");
        if ($common['qtydigits'] > 0) {
            return @number_format($qty, $common['qtydigits'], '.', '');
        } else {
            return round($qty);
        }
    }

    /**
     * форматирование  сумм  c  одной   цифрой  после  зарятой
     * например  для  сккидок
     * @param mixed $am
     * @return mixed
     */
    public static function fa1($am) {
        if (strlen($am) == 0) {
            return '';
        }  
        if( is_numeric($am) && abs($am)<0.005) {
            $am  =0;
        }
  
        $am = str_replace(',', '.', $am);

        $am = preg_replace("/[^0-9\.\-]/", "", $am);
        $am = trim($am);

 
        $am  = doubleval($am)  ;
        return @number_format($am, 1, '.', '');



    }

    /**
     * форматирование  сумм    с копейками
     *
     * @param mixed $am
     * @return mixed
     */
    public static function fa($am) {
        if (strlen($am) == 0) {
            return '';
        }  
        if( is_numeric($am) && abs($am)<0.005) {
            $am  =0;
        }
        $am = str_replace(',', '.', $am);
        $am = preg_replace("/[^0-9\.\-]/", "", $am);
        $am = trim($am);
     


        $am  = doubleval($am)  ;

        $common = System::getOptions("common");
        if ($common['amdigits'] == 1) {
            return @number_format($am, 2, '.', '');
        }
        if ($common['amdigits'] == 5) {
            $am = round($am * 20) / 20;
            return @number_format($am, 2, '.', '');
        }
        if ($common['amdigits'] == 10) {
            $am = round($am * 10) / 10;
            return @number_format($am, 2, '.', '');
        }

        return round($am);
    }

    /**
     * форматирование дат
     *
          * @return mixed
     */
    public static function fd($date) {
        if ($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if (strlen($dateformat) == 0) {
                $dateformat = 'd.m.Y';
            }

            return date($dateformat, $date);
        }

        return '';
    }

    /**
     * форматирование  даты и времени
     *
     * @param mixed $date
     * @return mixed
     */
    public static function fdt($date) {
        if ($date > 0) {
            $dateformat = System::getOption("common", 'dateformat');
            if (strlen($dateformat) == 0) {
                $dateformat = 'd.m.Y';
            }

            return date($dateformat . ' H:i', $date);
        }

        return '';
    }

    /**
     * форматирование  времени
     * @param mixed $date
     * @return mixed
     */
    public static function ft($date) {
        return date(' H:i', $date);
    }

    /**
     * возвращает  данные  фирмы.  Учитывает  филиал  если  задан
     */
    public static function getFirmData($firm_id = 0, $branch_id = 0) {
        $data = array();
        if ($firm_id > 0) {
            $firm = \App\Entity\Firm::load($firm_id);
            if ($firm == null) {
                $firm = \App\Entity\Firm::load(self::getDefFirm());
            }
            if ($firm != null) {
                $data = $firm->getData();
            }
        } else {
            $firm = \App\Entity\Firm::load(self::getDefFirm());
            if ($firm != null) {
                $data = $firm->getData();
            }
        }

        if ($branch_id > 0) {
            $branch = \App\Entity\Branch::load($branch_id);

            if (strlen($branch->address) > 0) {
                $data['address'] = $branch->address;
            }
            if (strlen($branch->phone) > 0) {
                $data['phone'] = $branch->phone;
            }
        }

        return $data;
    }

    /**
     * возвращает размер при пагинации
     *
     * @param mixed $pagesize
     * @return mixed
     */
    public static function getPG($pagesize = 0) {


        if ($pagesize > 0) {
            return $pagesize;
        }
        $user = \App\System::getUser();
        if ($user->pagesize > 0) {
            return $user->pagesize;
        }
        return 25;
    }

    /**
     * длина  номера  телефона
     *
     */
    public static function PhoneL() {

        $phonel = System::getOption("common", 'phonel');
        if ($phonel > 0) {
            return $phonel;
        }
        return 10;
    }

    /**
     * Возвращает языковую метку
     *
     * @param mixed $label
     * @param mixed $p1
     * @param mixed $p2
     * @deprecated
     */
    public static function l($label, $p1 = "", $p2 = "", $p3 = "") {
        return $label;

    }



    public static function getValList() {
        $val = \App\System::getOptions("val");
        if(!is_array($val['vallist'])) {
            $val['vallist'] = array();
        }
        $list = array();
        foreach($val['vallist'] as $v) {
            $list[$v->code]= $v->name   ;
        }

        return $list;
    }

    public static function getValName($vn) {
        if ($vn == 'Гривня') {
            return 'UAH';
        }
        if ($vn == 'Долар') {
            return 'USD';
        }
        if ($vn == 'Євро') {
            return 'EUR';
        }
        if ($vn == 'Рубль') {
            return 'RUB';
        }
        if ($vn == 'Лей') {
            return 'MDL';
        }
    }

    public static function exportXML($xml, $filename) {
        header("Content-type: text/xml");
        header("Content-Disposition: attachment;Filename={$filename}");
        header("Content-Transfer-Encoding: binary");

        echo $xml;
        die;
    }

    public static function exportExcel($data, $header, $filename) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        foreach ($header as $k => $v) {

            $sheet->setCellValue($k, $v);
            $sheet->getStyle($k)->applyFromArray([
                'font'      => [
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText'   => false,
                ]
            ]);
        }

        foreach ($data as $k => $v) {

            if (is_array($v)) {
                $c = $sheet->getCell($k);
                $style = $sheet->getStyle($k);
                if ($v['format'] == 'date') {
                    $v['value'] = date('d/m/Y', $v['value']);
                    $c->setValue($v['value']);
                    $style->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);
                } else {
                    if ($v['format'] == 'number') {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    } else {
                        $c->setValueExplicit($v['value'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                if ($v['bold'] == true) {
                    $style->getFont()->setBold(true);
                }
                if ($v['align'] == 'right') {
                    $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
            } else {
                //  $sheet->setCellValue($k, $v );
                $c = $sheet->getCell($k);
                $c->setValue($v);
                $c->setValueExplicit($v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        /*
          $sheet->getStyle('A1')->applyFromArray([
          'font' => [
          'name' => 'Arial',
          'bold' => true,
          'italic' => false,
          'underline' => Font::UNDERLINE_DOUBLE,
          'strikethrough' => false,
          'color' => [
          'rgb' => '808080'
          ]
          ],
          'borders' => [
          'allBorders' => [
          'borderStyle' => Border::BORDER_THIN,
          'color' => [
          'rgb' => '808080'
          ]
          ],
          ],
          'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
          'wrapText' => true,
          ]
          ]);

         */
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        die;
    }


    /**
    * Получение  дангный с  таблицы ключ-значение
    *
    * @param mixed $key
    * @return mixed
    */
    public static function getVal($key) {
        if(strlen($key)==0) {
            return;
        }
        $conn = \ZDB\DB::getConnect();

        $ret = $conn->GetOne("select vald from  keyval  where  keyd=" . $conn->qstr($key));

        if(strlen($ret)==0) {
            return "";
        }
        return $ret;
    }

    /**
    * Вставка  данных в  таблицу ключ-значение
    *
    * @param mixed $key
    * @param mixed $data
    * @return mixed
    */
    public static function setVal($key, $data=null) {
        if(strlen($key)==0) {
            return;
        }
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("delete  from  keyval  where  keyd=" . $conn->qstr($key));
        if($data===null) {
            return;
        }
        $conn->Execute("insert into keyval  (  keyd,vald)  values (" . $conn->qstr($key).",".$conn->qstr($data).")");


    }


    /**
    * Вставка  данных  в  таблицу  статистики
    *
    * @param mixed $cat
    * @param mixed $key
    * @param mixed $data
    * @return mixed
    */
    public static function insertstat(int $cat, int $key, int $data) {
        if($cat==0) {
            return;
        }

        $conn = \ZDB\DB::getConnect();
        $dt= $conn->DBTimeStamp(time());
        $conn->Execute("insert into stats  ( category, keyd,vald,dt)  values ({$cat},{$key},{$data},{$dt})");


    }






    /**
     * Печать  этикеток
     *
     * @param array $items
     */
    public static function printItems(array $items, $pqty=0) {
        $printer = \App\System::getOptions('printer');



        $htmls = "";

        foreach ($items as $item) {
            if(intval($item->item_id)==0) {
                continue;
            }
            $report = new \App\Report('item_tag.tpl');
            $header = [];

            if (strlen($item->shortname) > 0) {
                $header['name'] = $item->shortname;
            } else {
                $header['name'] = $item->itemname;
            }

            $header['name'] = str_replace("'", "`", $header['name'])  ;


            $header['isprice']   = $printer['pprice'] == 1;
            $header['isarticle']    = $printer['pcode'] == 1;
            $header['isbarcode'] = false;
            $header['isqrcode']  = false;


            $header['article'] = $item->item_code;
            $header['garterm'] = $item->warranty;
            $header['country'] = $item->country;
            $header['brand']   = $item->manufacturer;


            if (strlen($item->url) > 0 && $printer['pqrcode'] == 1) {
                $writer = new \Endroid\QrCode\Writer\PngWriter();

                $qrCode = new \Endroid\QrCode\QrCode($item->url);

                $qrCode->setSize(500);
                $qrCode->setMargin(5);

                $result = $writer->write($qrCode);

                $dataUri = $result->getDataUri();
                $header['qrcodeattr'] = "src=\"{$dataUri}\"  ";
                $header['qrcode'] = $item->url;
                $header['isqrcode'] = true;

            }


            if ($printer['pbarcode'] == 1) {

                $barcode = $item->bar_code;
                if (strlen($barcode) == 0) {
                    $barcode = $item->item_code;
                }
                if (strlen($barcode) > 0) {
                    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                    $da = " src=\"data:image/png;base64," . base64_encode($generator->getBarcode($barcode, $printer['barcodetype']))."\"" ;
                    $header['barcodeattr'] = $da;
                    $header['barcodewide'] = \App\Util::addSpaces($barcode);
                    $header['barcode'] = $barcode;
                    $header['isbarcode'] = true;

                }
            }

            $header['price'] = self::fa($item->getPrice($printer['pricetype']));
            if(intval($item->price) > 0) {
                $header['price'] = self::fa($item->price);  //по  документу
            }


            $qty =  intval($item->getQuantity());


            $printqty =  intval($item->printqty);
            if($printqty==0) {
                $printqty = 4;
            }

            if($printqty==1) {
                $qty = 1;
            }
            if($printqty==2) {
                $qty = 2;
            }
            if($printqty==3)  ;
            if($printqty==4) {
                if($qty > 10) {
                    $qty = 10;
                }
            }
            if(intval($item->quantity) > 0) {
                $qty = intval($item->quantity);  //по  документу
            }
            if($pqty >0) {
                $qty = $pqty;
            }
            for($i=0;$i< intval($qty) ;$i++) {
                $htmls = $htmls . $report->generate($header);
            }

        }
        $htmls = str_replace("\'", "", $htmls);

        return $htmls;
    }



    /**
     * Печать  этикеток на  ESC/POS
     *
     * @param array $items
     */
    public static function printItemsEP(array $items, $pqty=0) {
        $printer = \App\System::getOptions('printer');

        $htmls = "";

        foreach ($items as $item) {
            $report = new \App\Report('item_tag_ps.tpl');
            $header = [];
            if (strlen($item->shortname) > 0) {
                $header['name'] = $item->shortname;
            } else {
                $header['name'] = $item->itemname;
            }
            $header['name'] = str_replace("'", "`", $header['name'])  ;


            $header['isprice']   = $printer['pprice'] == 1;
            $header['isarticle'] = $printer['pcode'] == 1;
            $header['isbarcode'] = false;
            $header['isqrcode']  = false;


            $header['article'] = $item->item_code;
            $header['garterm'] = $item->warranty;
            $header['country'] = $item->country;
            $header['brand']   = $item->manufacturer;

            $header['price'] = self::fa($item->getPrice($printer['pricetype']));
            if(intval($item->price) > 0) {
                $header['price'] = self::fa($item->price);  //по  документу
            }


            if (strlen($item->url) > 0 && $printer['pqrcode'] == 1) {
                $header['qrcode'] = $item->url;
                $header['isqrcode']  = true;

            }
            if ($printer['pbarcode'] == 1) {

                $barcode = $item->bar_code;
                if (strlen($barcode) == 0) {
                    $barcode = $item->item_code;
                }
                if (strlen($barcode) > 0) {
                    $header['barcode'] = $barcode;
                    $header['isbarcode'] = true;
                }
            }

            $qty =  intval($item->getQuantity());

            $printqty =  intval($item->printqty);
            if($printqty==0) {
                $printqty = 4;
            }

            if($printqty==1) {
                $qty = 1;
            }
            if($printqty==2) {
                $qty = 2;
            }
            if($printqty==3)  ;
            if($printqty==4) {
                if($qty > 10) {
                    $qty = 10;
                }
            }
            if(intval($item->quantity) > 0) {
                $qty = intval($item->quantity);  //по  документу
            }
            if($pqty >0) {
                $qty = $pqty;
            }

            for($i=0;$i< intval($qty) ;$i++) {
                $htmls = $htmls . $report->generate($header);
            }

            for($i=0;$i<$qty;$i++) {
                $htmls = $htmls .   $report->generate($header) ;
            }
        }

        return $htmls;
    }

    
    public  static function getSalt(){
         $salt= self::getVal('salt');
         if(strlen($salt)==0)  {
            $salt = ''. rand(1000,999999) ;
            self::setVal('salt',$salt);             
         }
         return $salt;
    }
    
}
