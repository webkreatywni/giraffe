<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OrderDaysLeft
 *
 * @author = "piter";
 */
class Zend_View_Helper_OrderRowClass extends Zend_View_Helper_Url{
    
    public function orderRowClass($order)
    {
        $class = '';
        $isNew = (bool) !empty($order->date_of_payment) && !empty($order->date_of_receipt);
        if($isNew){
            $class = 'order-is-finalized';
        }
        return $class;
    }
}

?>
