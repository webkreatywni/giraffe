<?php

class Application_Model_DbTable_Order extends Zend_Db_Table_Abstract
{
    protected $_rowClass = 'Application_Model_DbTable_Order_Row';
    protected $_name = 'orders';
    protected $_defaultSort = 'date_of_payment';
    protected $_defaultDir = 'asc';
    
    public function applyOrdersSelectFilters($filtersData,  Zend_Db_Select $select)
    {
        if(array_key_exists('client', $filtersData)){
            $clientName = $filtersData['client'];
            $select = $this->getOrdersSelectByClient($clientName, $select);
        }
        
        if(array_key_exists('order_year', $filtersData)){
            $insertYear = $filtersData['order_year'];
            $select = $this->getOrdersSelectByInsertYear($insertYear, $select);
        }
        return $this->fetchAll($select);
        return $select;
    }
    
    public function getOrdersReceivedOn($date)
    {
        $select = $this->select()->where('date_of_receipt = ?', $date);
        $rowset = $this->fetchAll($select);
        return $rowset;
    }
    
    public function getAllOrders($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        $select = $this->getAllOrdersSelect($sortField, $sortDir);
        return $this->fetchAll($select);
    }
    
    public function getPaidNotSentOrders($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        $select = $this->getPaidNotSentOrdersSelect($sortField, $sortDir);
        return $this->fetchAll($select);
    }
    
    public function getPaidOrders($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        $select = $this->getPaidOrdersSelect($sortField, $sortDir);
        return $this->fetchAll($select);
    }
    
    public function getPaidSentOrders($sortField = null, $sortDir = 'asc')
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        $select = $this->getPaidSentOrdersSelect($sortField, $sortDir);
        return $this->fetchAll($select);
    }
    
    public function getUnpaidOrders($sortField = null, $sortDir = 'asc')
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        $select = $this->getUnpaidOrdersSelect($sortField, $sortDir);
        return $this->fetchAll($select);
    }
    
    public function getPaidOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        
        $select->where('date_of_payment IS NOT NULL');
        return $select;
    }
    
    public function getAllOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        
        return $select;
    }
    
    public function getUnpaidNotSentOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        
        $select = $this->getUnpaidOrdersSelect(null, null, $select);
        $select->orWhere('date_of_receipt IS NULL');
        
        return $select;
    }
    
    public function getPaidNotSentOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        
        $select->where('date_of_payment IS NOT NULL');
        $select->where('date_of_receipt IS NULL');
        return $select;
    }
    
    public function getPaidSentOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        $select->where('date_of_receipt IS NOT NULL');
        
        $select = $this->getPaidOrdersSelect(null, null, $select);
        
        return $select;
    }
    
    public function getUnpaidOrdersSelect($sortField = null, $sortDir = 'asc', Zend_Db_Select $select = null)
    {
        if(is_null($sortField)){
            $sortField = $this->_defaultSort;
        }
        
        if(is_null($select)){
            $select = $this->getOrderedOrdersSelect($sortField, $sortDir);
        }
        
        $select->where('date_of_payment IS NULL');
        return $select;
    }
    
    public function getOrderedOrdersSelect($sortField, $sortDir, Zend_Db_Select $select = null)
    {
        if(in_array($sortField, array('date_of_payment', 'date_of_receipt'))){
            $sortField = new Zend_Db_Expr("ISNULL({$sortField}), {$sortField}");
        }
        
        $order = "$sortField $sortDir";
        
        if(is_null($select)){
            $select = $this->select();
        }
        
        $select->order($order);
        
        return $select;
    }
    
    public function getOrdersSelectByClient($clientName, Zend_Db_Select $select = null)
    {
        if(is_null($select)){
            $select = $this->select()->order("$this->_defaultSort $this->_defaultDir");
        }
        
        if(!is_null($clientName) && preg_match('/\b(\w)+\b/', $clientName)){
            $select->where("client like ?", "%{$clientName}%");
            return $select;
        } else {
            throw new Zend_Exception("Argument clientName: $clientName is not valid");
        }
    }
    
    
    public function getOrdersSelectByInsertYear($insertYear, Zend_Db_Select $select = null)
    {
        if(is_null($select)){
            $select = $this->select()->order("$this->_defaultSort $this->_defaultDir");
        }
        
        if(preg_match('/\b(\d){4}\b/', $insertYear)){
            $dateExpression = new Zend_Db_Expr("DATE_FORMAT(insert_time, '%Y')");
            $select->where("$dateExpression = ?", $insertYear);
            return $select;
        } else {
            throw new Zend_Exception("Argument insertYear: $insertYear is not valid");
        }
    }
    
    public function getUnpaidNotSentOrdersCount()
    {
        $rowset = $this->fetchAll($this->getUnpaidNotSentOrdersSelect());
        return $rowset->count();
    }
    
    public function getOrdersCount()
    {
        $rowset = $this->fetchAll($this->getAllOrdersSelect());
        return $rowset->count();
    }
    
    public function getPaidNotSentOrdersCount()
    {
        $rowset = $this->getPaidNotSentOrders();
        return $rowset->count();
    }
    
    public function getUnpaidOrdersCount()
    {
        $rowset = $this->getUnpaidOrders();
        return $rowset->count();
    }
    
    public function getPaidSentOrdersCount()
    {
        $rowset = $this->getPaidSentOrders();
        return $rowset->count();
    }

    public function getLastOrderId()
    {
        $id = null;
        $select = $this->select()->order('id DESC')->limit(1);
        $row = $this->fetchRow($select);

        if($row instanceof Zend_Db_Table_Row){
            $id = $row->id;
        }

        return $id;
    }
}

