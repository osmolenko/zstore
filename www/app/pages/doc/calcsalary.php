<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Entity\Employee;
use App\Entity\MoneyFund;
use App\Entity\SalType;
use App\Entity\EmpAcc;
use App\Helper as H;
use App\System;

use Zippy\Html\Label;

/**
 * Страница   начисление  зарплаты
 */
class CalcSalary extends \App\Pages\Base
{
    private $_doc;
    private $_list;


    public function __construct($docid = 0) {
        parent::__construct();


        $this->_list = Employee::find('disabled<>1', 'emp_name');
        $this->_stlist = SalType::find("disabled<>1", "salcode");



        if ($docid > 0) {    //загружаем   содержимое  документа на страницу
            $this->_doc = Document::load($docid)->cast();

            $this->_list = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('CalcSalary');
            $this->_doc->document_number = $this->_doc->nextNumber();
            $this->_doc->document_date = time();
            $this->_doc->headerdata['daysmon'] =22;
            $this->_doc->headerdata['year'] = round(date('Y'));
            $this->_doc->headerdata['month'] = round(date('m'));
        }


        if ($this->_doc->document_id == 0) {

            foreach ($this->_list as $emp) {
                foreach ($this->_stlist as $st) {
                    $c   = "_c".$st->salcode ;
                    $emp->{$c} = 0;
                }
            }



            if (($opt['codeadvance'] ??0) > 0) { //аванс

                $rows = EmpAcc::getAmountByType($this->_doc->headerdata['year'], $this->_doc->headerdata['month'], EmpAcc::ADVANCE);
                foreach ($rows as $row) {
                    $c = '_c' . $opt['codeadvance'];
                    $this->_list[$row['emp_id']]->{$c} = 0 - H::fa($row['am']);
                }
            }


        }

        $opt = System::getOptions("salary");


        $calcvar ='';
        //переменные  из настроек
        $calcvar .= "var daysmon = this.doc.daysmon \n"  ;
        $calcvar .= "var invalid = emp.invalid   \n" ;
        $calcvar .= "var salarytype = emp.salarytype   \n" ;
        $calcvar .= "var sellvalue = emp.sellvalue   \n" ;
        $calcvar .= "var salarym = emp.salarym   \n" ;
        $calcvar .= "var salaryh = emp.salaryh   \n" ;
        $calcvar .= "var hours = emp.hours   \n" ;
        $calcvar .= "var days = emp.days   \n" ;

        // из  строки сотрудника  в переменные
        foreach($this->_stlist as $st) {
            $ret['stlist'][]  = array("salname"=>$st->salshortname,"salcode"=>'_c'.$st->salcode);

            $calcvar .= "var v{$st->salcode} =  parseVal(emp['_c{$st->salcode}'] ) ;\n ";

        }

        $calc = $calcvar;
        $calc .= "\n\n";
        $calc .= $opt['calc'];  //формулы
        $calc .= "\n\n";

        $calcbase = $calcvar;
        $calcbase .= "\n\n";
        $calcbase .= $opt['calcbase'];  //формулы
        $calcbase .= "\n\n";

        // из  переменных в строку  сотрудника

        foreach($this->_stlist as $st) {

            $calc .= "emp['_c{$st->salcode}']  = parseVal( v{$st->salcode}) ;\n ";
            $calcbase .= "emp['_c{$st->salcode}']  = parseVal( v{$st->salcode}) ;\n ";

        }

        $this->_tvars['calcs'] = $calc;
        $this->_tvars['calcbases'] = $calcbase;



    }


    public function save($args, $post) {
        $post = json_decode($post) ;
        if (false == \App\ACL::checkEditDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав редагування документу' ], JSON_UNESCAPED_UNICODE);
        }

        $this->_doc->document_number = $post->doc->document_number;
        $this->_doc->document_date = strtotime($post->doc->document_date);
        $this->_doc->notes = $post->doc->notes;
        $this->_doc->headerdata['daysmon'] = $post->doc->daysmon;
        $this->_doc->headerdata['year'] = $post->doc->year;
        $this->_doc->headerdata['month'] = $post->doc->month;
        $mlist = \App\Util::getMonth();
        $this->_doc->headerdata['monthname'] = $mlist[$post->doc->month] ;



        if (false == $this->_doc->checkUniqueNumber()) {
            return json_encode(['error'=>'Не унікальний номер документу. Створено новий.','newnumber'=>$this->_doc->nextNumber()], JSON_UNESCAPED_UNICODE);
        }


        $opt = System::getOptions("salary");


        $elist = $post->emps;

        $this->_doc->amount = 0 ;
        $this->_list = array();

        $emps = Employee::find('disabled<>1', 'emp_name');


        foreach($elist as $e) {
            $emp = $emps[$e->id];
            $this->_doc->amount +=  $e->{'_c'.$opt['coderesult']}    ;

            foreach ($this->_stlist as $st) {
                $c   = "_c".$st->salcode ;
                $emp->{$c} = $e->{$c};
            }


            $this->_list[]= $emp;
        }

        $this->_doc->packDetails('detaildata', $this->_list);

        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {

            $this->_doc->save();
            if ($post->op == 'execdoc') {
                if (!$isEdited) {
                    $this->_doc->updateStatus(Document::STATE_NEW);
                }
                $this->_doc->updateStatus(Document::STATE_EXECUTED);
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }
            $conn->CommitTrans();

        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return json_encode(['error'=>$ee->getMessage()], JSON_UNESCAPED_UNICODE);


        }

        return json_encode([], JSON_UNESCAPED_UNICODE);
    }




    public function loaddata($args, $post) {
        $opt = System::getOptions("salary");

        $post = json_decode($post) ;
        $conn = \ZDB\DB::getConnect();

        $from =''.$post->year .'-'. $post->month .'-01' ;
        $from =  strtotime($from);
        $to =   strtotime('+1 month', $from) - 1 ;

        $from = $conn->DBDate($from) ;
        $to = $conn->DBDate($to) ;


        $br = "";
        $brids = \App\ACL::getBranchIDsConstraint();
        if (strlen($brids) > 0) {
            $br = " and d.branch_id in ({$brids}) ";
        }

        $sqlitem = "
                  select   sum(0-e.quantity*e.outprice) as summa 
                      from entrylist_view  e

                      join items i on e.item_id = i.item_id
                     join documents_view d on d.document_id = e.document_id
                       where e.item_id >0  and (e.tag = 0 or e.tag = -1  or e.tag = -4) 
                      and d.meta_name in ('GoodsIssue','ServiceAct' ,'POSCheck','ReturnIssue','TTN','OrderCust','OrderFood')           
                       {$br}  AND DATE(e.document_date) >= {$from}   AND DATE(e.document_date) <= " .$to  ;


        $sqlservice ="        select  sum(0-e.outprice*e.quantity) as summa    
                      from entrylist_view  e

                      join services s on e.service_id = s.service_id
                     join documents_view d on d.document_id = e.document_id
                       where e.service_id >0  and e.quantity <>0      {$cust}  
                      and d.meta_name in (  'ServiceAct' ,'POSCheck' )
                       {$br}  AND DATE(e.document_date) >={$from} 
                      AND DATE(e.document_date) <= " . $to ;



        $ret=[];
        $ret['newdoc'] = $this->_doc->document_id == 0 ;
        $ret['emps'] = array() ;

        $ret['opt'] = array() ;
        $ret['opt']['coderesult']  =  $opt['coderesult'];
        $ret['opt']['codebaseincom']  =  $opt['codebaseincom'];



        foreach ($this->_list as $emp) {
            $e = array('emp_name'=>$emp->emp_name,'id'=>$emp->employee_id);
            $e['sellvalue'] = 0;
            $u = \App\Entity\User::getByLogin($emp->login) ;
            if($u != null) {
                $sql= $sqlitem ." and d.user_id=" .$u->user_id;
                $e['sellvalue'] = intval($conn->GetOne($sql)) ;
                $sql= $sqlservice ." and d.user_id=" .$u->user_id;
                $e['sellvalue'] = $e['sellvalue'] + intval($conn->GetOne($sql)) ;
            }


            $sql="select sum(tm) as tm, count(distinct dd) as dd   from (select  date(t_start) as dd, (UNIX_TIMESTAMP(t_end)-UNIX_TIMESTAMP(t_start)  - t_break*60)   as  tm from timesheet where t_type=1  and  emp_id = {$emp->employee_id} and  date(t_start)>=date({$from}) and  date( t_start)<= date( {$to} ) ) t   ";
            if($conn->dataProvider=="postgres") {
                $sql="select sum(tm) as tm, count(distinct dd) as dd   from (select  date(t_start) as dd, (extract(epoch from t_end) - extract(epoch from t_start)  - t_break*60)   as  tm from timesheet where t_type=1  and  emp_id = {$emp->employee_id} and  date(t_start)>=date({$from}) and  date( t_start)<= date( {$to} ) ) t   ";
            }

            $t = $conn->GetRow($sql);
            $e['hours']  = intval($t['tm']/3600);
            $e['days']   = doubleval($t['dd']);

            $e['invalid'] = $emp->invalid == 1  ;
            $e['salarytype'] = $emp->ztype ;
            $e['salarym'] = $emp->zmon  ;
            $e['salaryh'] = $emp->zhour  ;


            foreach($this->_stlist as $st) {
                $e['_c'.$st->salcode]  =  $emp->{'_c'.$st->salcode};
            }

            $ret['emps'][] = $e;
        }

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }


    public function loaddoc($args, $post) {

        if (false == \App\ACL::checkShowDoc($this->_doc, false, false)) {

            return json_encode(['error'=>'Нема прав на  доступ до документу' ], JSON_UNESCAPED_UNICODE);
        }


        $opt = System::getOptions("salary");
        $ret=[];

        $ret['years'] =  \App\Util::tokv(\App\Util::getYears());
        $ret['monthes'] = \App\Util::tokv(\App\Util::getMonth()) ;


        foreach($this->_stlist as $st) {
            $ret['stlist'][]  = array("salname"=>$st->salshortname,"salcode"=>'_c'.$st->salcode);
        }

        $ret['doc'] = [] ;
        $ret['doc']['document_date']   =  date('Y-m-d', $this->_doc->document_date) ;
        $ret['doc']['document_number']   =   $this->_doc->document_number ;
        $ret['doc']['notes']   =   $this->_doc->notes ;
        $ret['doc']['daysmon']   =   $this->_doc->headerdata['daysmon'] ;
        $ret['doc']['year']   =   $this->_doc->headerdata['year'] ;
        $ret['doc']['month']   =   $this->_doc->headerdata['month'] ;


        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

}
