<?php

namespace App\Pages\Doc;

use App\Application as App;
use App\Entity\Customer;
use App\Entity\MoneyFund;
use App\Entity\Doc\Document;
use App\Entity\Item;

use App\Helper as H;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;

/**
 * Страница  ввода  заказа
 */
class Order extends \App\Pages\Base
{
    public $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid     = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new CheckBox('production'));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new \Zippy\Html\Link\BookmarkableLink('cinfo'))->setVisible(false);
        $this->docform->add(new TextArea('notes'));
        $this->docform->add(new DropDownChoice('payment', MoneyFund::getList(), 0));
        $this->docform->add(new DropDownChoice('salesource', H::getSaleSources(), H::getDefSaleSource()));



        $this->docform->add(new TextInput('editbonus'));
        $this->docform->add(new SubmitButton('bbonus'))->onClick($this, 'onBonus');
        $this->docform->add(new Label('bonus', 0));

        $this->docform->add(new TextInput('edittotaldisc'));
        $this->docform->add(new SubmitButton('btotaldisc'))->onClick($this, 'onTotaldisc');
        $this->docform->add(new Label('totaldisc', 0));



        $this->docform->add(new TextInput('payed', 0));
        $this->docform->add(new Label('payamount', 0));

        $this->docform->add(new Label('custinfo'))->setVisible(false);
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()))->onChange($this, 'OnChangePriceType');

        $this->docform->add(new DropDownChoice('delivery', Document::getDeliveryTypes($this->_tvars['np'] == 1)))->onChange($this, 'OnDelivery');
        $this->docform->add(new TextInput('email'));
        $this->docform->add(new TextInput('phone'));
        $this->docform->add(new TextArea('address'))->setVisible(false);

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('topaydoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('paydoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total'));


        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));
        $this->editdetail->add(new TextInput('editdesc'));

        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true);
        $this->editdetail->add(new ClickLink('openitemsel', $this, 'onOpenItemSel'));
        $this->editdetail->add(new ClickLink('opencatpan', $this, 'onOpenCatPan'));

        $this->editdetail->add(new Label('qtystock'));
        $this->editdetail->add(new Label('pricestock'));
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelrowOnClick');
        $this->editdetail->add(new SubmitButton('submitrow'))->onClick($this, 'saverowOnClick');
        $this->add(new \App\Widgets\ItemSel('wselitem', $this, 'onSelectItem'))->setVisible(false);

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editcustphone'));
        $this->editcust->add(new TextArea('editcustcomment'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        //добавление нового товара
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new TextInput('editnewbrand'));
        $this->editnewitem->add(new TextInput('editnewmsr'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new DropDownChoice('editnewcat', \App\Entity\Category::getList(), 0));
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');


        if ($docid > 0) {    //загружаем   содержимое  документа настраницу
            $this->_doc = Document::load($docid)->cast();
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);
            $this->docform->totaldisc->setText($this->_doc->headerdata['totaldisc']);


            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->OnDelivery($this->docform->delivery);
            $this->docform->production->setChecked($this->_doc->headerdata['production']);
            $this->docform->payment->setValue($this->_doc->headerdata['payment']);
            $this->docform->salesource->setValue($this->_doc->headerdata['salesource']);
            $this->docform->total->setText($this->_doc->amount);

            $this->docform->payamount->setText($this->_doc->payamount);

            $this->docform->bonus->setText($this->_doc->headerdata['bonus']);
            $this->docform->editbonus->setText($this->_doc->headerdata['bonus']);

            $this->docform->totaldisc->setText($this->_doc->headerdata['totaldisc']);
            $this->docform->edittotaldisc->setText($this->_doc->headerdata['totaldisc']);

            if ($this->_doc->payed == 0 && $this->_doc->headerdata['payed'] > 0) {
                $this->_doc->payed = $this->_doc->headerdata['payed'];
            }


            $this->docform->payed->setText(H::fa($this->_doc->payed));

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->email->setText($this->_doc->headerdata['email']);
            $this->docform->phone->setText($this->_doc->headerdata['phone']);
            $this->docform->address->setText($this->_doc->headerdata['ship_address']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->OnCinfo($this->_doc->customer_id);


            $this->_tovarlist = $this->_doc->unpackDetails('detaildata');
        } else {
            $this->_doc = Document::create('Order');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {

                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->docform->notes->setText($basedoc->notes);
                        $this->docform->email->setText($basedoc->headerdata['email']);
                        $this->docform->phone->setText($basedoc->headerdata['phone']);
                        $this->docform->address->setText($basedoc->headerdata['ship_address']);

                        $this->docform->payment->setValue($basedoc->headerdata['payment']);
                        $this->docform->salesource->setValue($basedoc->headerdata['salesource']);
                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->totaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->edittotaldisc->setText($basedoc->headerdata['totaldisc']);
                        $this->docform->bonus->setText($basedoc->headerdata['bonus']);
                        $this->docform->editbonus->setText($basedoc->headerdata['bonus']);
                        $this->docform->payamount->setText($basedoc->payamount);
                        $this->docform->delivery->setValue($basedoc->headerdata['delivery']);
                        $this->OnDelivery($this->docform->delivery);
                        $this->docform->production->setChecked($basedoc->headerdata['production']);
                        $this->_tovarlist = $basedoc->unpackDetails('detaildata');


                        $this->docform->total->setText(H::fa($basedoc->amount));

                        $this->calcPay();


                    }



                }
            }
        }


        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc)) {
            return;
        }


    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('tovar', $item->itemname));

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('msr', $item->msr));
        $row->add(new Label('desc', $item->desc));

        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('disc', H::fa($item->disc)));
        $row->add(new Label('price', H::fa($item->price)));

        $row->add(new Label('amount', H::fa($item->quantity * $item->price)));
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $item = $sender->owner->getDataItem();
        $rowid =  array_search($item, $this->_tovarlist, true);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($rowid => $this->_tovarlist[$rowid]));

        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->editdetail->editdesc->setText("");
        $this->docform->setVisible(false);
        $this->_rowid = -1;
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);
        $this->editdetail->editdesc->setText($item->desc);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->_rowid =  array_search($item, $this->_tovarlist, true);

    }

    public function saverowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не обрано товар");
            return;
        }

        $item = Item::load($id);

        $item->quantity = $this->editdetail->editquantity->getText();

        $item->price = $this->editdetail->editprice->getText();


        $item->disc = '';
        $item->pureprice = $item->getPurePrice();
        if($item->pureprice > $item->price) {
            $item->disc = number_format((1 - ($item->price/($item->pureprice)))*100, 1, '.', '') ;
        }



        $item->desc = $this->editdetail->editdesc->getText();

        if($this->_rowid == -1) {
            $this->_tovarlist[] = $item;
            $this->addrowOnClick(null);
            $this->setInfo("Позиція додана") ;
            //очищаем  форму
            $this->editdetail->edittovar->setKey(0);
            $this->editdetail->edittovar->setText('');

            $this->editdetail->editquantity->setText("1");

        } else {
            $this->_tovarlist[$this->_rowid] = $item;
            $this->cancelrowOnClick(null);

        }


        $this->docform->detail->Reload();
        $this->calcTotal();
        $this->calcPay();

    }

    public function cancelrowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->wselitem->setVisible(false);


    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc)) {
            return;
        }
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->_doc->customer_id > 0) {
            $customer = Customer::load($this->_doc->customer_id);
            $this->_doc->headerdata['customer_name'] = $this->docform->customer->getText();
        }


        $this->_doc->headerdata['totaldisc'] = $this->docform->totaldisc->getText();

        $this->_doc->headerdata['delivery'] = $this->docform->delivery->getValue();
        $this->_doc->headerdata['delivery_name'] = $this->docform->delivery->getValueName();
        $this->_doc->headerdata['ship_address'] = $this->docform->address->getText();
        $this->_doc->headerdata['phone'] = $this->docform->phone->getText();
        $this->_doc->headerdata['email'] = $this->docform->email->getText();
        $this->_doc->headerdata['pricetype'] = $this->docform->pricetype->getValue();
        $this->_doc->headerdata['production'] = $this->docform->production->isChecked() ? 1 : 0;

        $this->_doc->packDetails('detaildata', $this->_tovarlist);

        $this->_doc->amount = $this->docform->total->getText();

        $this->_doc->payamount = $this->docform->payamount->getText();


        $this->_doc->headerdata['bonus'] = $this->docform->bonus->getText();
        $this->_doc->headerdata['totaldisc'] = $this->docform->totaldisc->getText();

        $this->_doc->headerdata['salesource'] = $this->docform->salesource->getValue();


        if ($this->checkForm() == false) {
            return;
        }
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();


        try {
            if ($this->_basedocid > 0) {
                $this->_doc->parent_id = $this->_basedocid;
                $this->_basedocid = 0;
            }
            $this->_doc->payed = 0;
            $this->_doc->headerdata['payed'] = 0;
            $this->_doc->headerdata['payment'] = 0;

            if ($sender->id == 'paydoc') {
                $this->_doc->payed = $this->docform->payed->getText();
                $this->_doc->headerdata['payed'] = $this->docform->payed->getText();
                $this->_doc->headerdata['payment'] = $this->docform->payment->getValue();

                if ($this->_doc->payed > $this->_doc->payamount) {
                    $this->setError('Внесена сума більше необхідної');
                    return;
                }
                if ($this->_doc->payed == 0) {
                    return;
                }
                if ($this->docform->payment->getValue() == 0 && $this->_doc->payed > 0) {
                    $this->setError("Якщо внесена сума більше нуля, повинна бути обрана каса або рахунок");
                    return;
                }


            }

            $this->_doc->save();

            if ($sender->id == 'savedoc') {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }


            if ($sender->id == 'execdoc' || $sender->id == 'paydoc' || $sender->id == 'topaydoc') {
                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            }
            if ($sender->id == 'topaydoc') {
                $this->_doc->updateStatus(Document::STATE_WP);
            }



            $conn->CommitTrans();
            if ($sender->id == 'execdoc') {
                // App::Redirect("\\App\\Pages\\Doc\\TTN", 0, $this->_doc->document_id);
            }


            if (false == \App\ACL::checkShowReg('OrderList', false)) {
                App::RedirectHome() ;
            } else {
                App::Redirect("\\App\\Pages\\Register\\OrderList");
            }



        } catch(\Throwable $ee) {
            global $logger;
            $conn->RollbackTrans();
            if ($isEdited == false) {
                $this->_doc->document_id = 0;
            }
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;


        foreach ($this->_tovarlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total->setText(H::fa($total));


    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введіть номер документа');
        }
        if (false == $this->_doc->checkUniqueNumber()) {
            $next = $this->_doc->nextNumber();
            $this->docform->document_number->setText($next);
            $this->_doc->document_number = $next;
            if (strlen($next) == 0) {
                $this->setError('Не створено унікальный номер документа');
            }
        }
        if (count($this->_tovarlist) == 0) {
            $this->setError("Не введено товар");
        }

        $c = $this->docform->customer->getKey();
        if ($c == 0) {
            $this->setError("Не задано контрагента");
        }



        return !$this->isError();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeItem($sender) {
        $id = $sender->getKey();
        $item = Item::load($id);
        $customer_id = $this->docform->customer->getKey()  ;
        $pt=     $this->docform->pricetype->getValue() ;
        $price = $item->getPriceEx(array(
           'pricetype'=>$pt,
           'customer'=>$customer_id
         ));

        $this->editdetail->qtystock->setText(H::fqty($item->getQuantity()));
        $this->editdetail->editprice->setText($price);
        $price = $item->getLastPartion();
        $this->editdetail->pricestock->setText(H::fa($price));


    }

    public function OnAutoCustomer($sender) {
        return Customer::getList($sender->getText(), 1);
    }

    public function OnChangeCustomer($sender) {
        $disc = 0;
        $bonus = 0;

        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);

            if (strlen($customer->pricetype) > 4) {
                $this->docform->pricetype->setValue($customer->pricetype);
            }


            $this->docform->phone->setText($customer->phone);
            $this->docform->email->setText($customer->email);
            $this->docform->address->setText($customer->address);
            $d= $customer->getDiscount();

            if (doubleval($d) > 0) {
                $disctext = "Постійна знижка {$d}%";
            } else {
                $bonus = $customer->getBonus();
                if ($bonus > 0) {
                    $disctext = "Нараховано бонусів {$bonus} ";
                }
            }
            $this->docform->custinfo->setText($disctext);
            $this->docform->custinfo->setVisible(strlen($disctext) >0);
            $this->docform->cinfo->setVisible(false);



            $this->OnCinfo($customer_id);

        }


        $this->calcTotal();

        $this->calcPay();
    }


    private function OnCinfo($customer_id) {
        $customer_id=intval($customer_id)  ;
        if($customer_id==0) {
            return;
        }

        $this->docform->cinfo->setVisible(true) ;
        $this->docform->cinfo->setAttribute('onclick', "customerInfo({$customer_id});") ;

    }

    public function OnAutoItem($sender) {
        return Item::findArrayAC($sender->getText());
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editcustphone->setText('');
        $this->editcust->editcustcomment->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->customer_name = $this->editcust->editcustname->getText();
        $cust->phone = $this->editcust->editcustphone->getText();
        $cust->phone = \App\Util::handlePhone($cust->phone);

        if (strlen($cust->phone) > 0 && strlen($cust->phone) != H::PhoneL()) {
            $this->setError("Довжина номера телефона повинна бути ".\App\Helper::PhoneL()." цифр");
            return;
        }

        $c = Customer::getByPhone($cust->phone);
        if ($c != null) {
            if ($c->customer_id != $cust->customer_id) {
                $this->setError("Вже існує контрагент з таким телефоном");
                return;
            }
        }

        $cust->comment = $this->editcust->editcustcomment->getText();

        $cust->type = 1;
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);
        $this->docform->phone->setText($cust->phone);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->custinfo->setVisible(false);
        $this->docform->cinfo->setVisible(false);

        $this->docform->phone->setText($cust->phone);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnDelivery($sender) {
        $dt = $sender->getValue() ;
        if ($dt == Document::DEL_NP || $dt== Document::DEL_BOY || $dt == Document::DEL_SERVICE) {
            $this->docform->address->setVisible(true);
        } else {
            $this->docform->address->setVisible(false);
        }
    }

    public function OnChangePriceType($sender) {
        foreach ($this->_tovarlist as $item) {
            //$item = Item::load($item->item_id);
            $price = $item->getPrice($this->docform->pricetype->getValue());
            $item->price = $price;
        }
        $this->calcTotal();
        $this->docform->detail->Reload();
        $this->calcTotal();
    }



    public function onBonus() {
        $this->docform->bonus->setText($this->docform->editbonus->getText());
        $this->calcPay();
        $this->goAnkor("tankor");
    }

    public function onTotaldisc($sender) {
        $this->docform->totaldisc->setText($this->docform->edittotaldisc->getText());

        $this->calcPay();


        $this->goAnkor("tankor");
    }

    private function calcPay() {
        $total = $this->docform->total->getText();

        $bonus = $this->docform->bonus->getText();
        $totaldisc = $this->docform->totaldisc->getText();

        if ($bonus > 0) {
            $total -= $bonus;
        }
        if ($totaldisc > 0) {
            $total -= $totaldisc;
        }



        $this->docform->payamount->setText(H::fa($total));

        $this->docform->payed->setText(H::fa($total));


    }


    public function onSelectItem($item_id, $itemname) {
        $this->editdetail->edittovar->setKey($item_id);
        $this->editdetail->edittovar->setText($itemname);
        $this->OnChangeItem($this->editdetail->edittovar);
    }

    public function onOpenItemSel($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->wselitem->Reload();
    }
    public function onOpenCatPan($sender) {
        $this->wselitem->setVisible(true);
        $this->wselitem->setPriceType($this->docform->pricetype->getValue());
        $this->wselitem->Reload(true);
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);
        $this->wselitem->setVisible(false);

        $this->editnewitem->clean();
        $this->editnewitem->editnewbrand->setDataList(Item::getManufacturers());
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено назву");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->msr = $this->editnewitem->editnewmsr->getText();

        if (strlen($item->item_code) > 0) {
            $code = Item::qstr($item->item_code);
            $cnt = Item::findCnt("  item_code={$code} ");
            if ($cnt > 0) {
                $this->setError('Такий артикул вже існує');
                return;
            }

        } else {
            if (\App\System::getOption("common", "autoarticle") == 1) {

                $item->item_code = Item::getNextArticle();
            }
        }


        $item->manufacturer = $this->editnewitem->editnewbrand->getText();
        $item->cat_id = $this->editnewitem->editnewcat->getValue();
        $item->save();
        $this->editdetail->edittovar->setText($item->itemname);
        $this->editdetail->edittovar->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function cancelnewitemOnClick($sender) {
        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function getPriceByQty($args, $post=null) {
        $item = Item::load($args[0]) ;
        $args[1] = str_replace(',', '.', $args[1]) ;
        $price = $item->getActionPriceByQuantity($args[1]);
        return  $price;

    }



}
