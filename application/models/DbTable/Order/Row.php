<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Row
 *
 * @author = "piter";
 */
class Application_Model_DbTable_Order_Row extends Zend_Db_Table_Row {

    public function __toString()
    {
        $properties = array();
        $properties[] = $this->id;
        $properties[] = $this->unique;
        $properties[] = $this->model;
        $properties[] = $this->invoice_id;
        $properties[] = $this->bonus;
        $properties[] = $this->wheels;
        $properties[] = $this->client;
        $properties[] = $this->date_of_payment;
        $properties[] = $this->date_of_receipt;

        $result = implode('<br />', $properties);

        return $result;
    }
}

?>