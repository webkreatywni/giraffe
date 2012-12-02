<?php

class OrderController extends My_Controller_Action
{
    protected $_currentRouteName = '';
    
    protected $_gridViewName = 'orders/table.phtml';
    
    protected $_gridOptions = array();

    protected $_exportOptions = array('pdf');

    protected $_nullColumns = array(
        'date_of_receipt',
        'date_of_payment'
    );
    
    protected $_lastRequestParams = null;
    
    public function preDispatch()
    {
        $this->saveRequestBeforeUpdate();
    }
    
    public function init()
    {
        parent::init();
        
        $this->view->tableViewName = $this->_gridViewName;
        
        $export = $this->getRequest()->getParam('export', null);
        if(in_array($export, $this->_exportOptions)){
            $this->_exportToPdf();
        }
        
        $this->_contextSwitch
             ->addActionContext('orders-received-tomorrow', 'json')
                ->setAutoJsonSerialization(false)
                    ->initContext();
        
        $Order = new Application_Model_DbTable_Order();
        $numbers = array();
        $numbers['all'] = $Order->getUnpaidNotSentOrdersCount();
        $numbers['unpaid'] = $Order->getUnpaidOrdersCount();
        $numbers['paid'] = $Order->getPaidNotSentOrdersCount();
        $numbers['sent'] = $Order->getPaidSentOrdersCount();
        $this->view->ordersNumbers = $numbers;
        
        $requestUri =$this->getRequest()->getRequestUri();
        $requestUri = explode('/', $requestUri);
        $requestUri = array_slice($requestUri, 0, 3);
        $requestUri = implode('/', $requestUri);
        $this->view->currentUri = $requestUri;
        
        $this->_gridOptions['url'] = $this->view->url();
        $this->_gridOptions['export'] = array('pdf' => array('label' => 'PDF'));
        $this->_gridOptions['filter'] = array('order_year' => array('label' => 'Zamówienia za rok', 'values' => array('2012' => '2012', '2013' => '2013', '2014' => '2014', '2015' => '2015')));
        $this->_gridOptions['massactions'] = array('export');
        $this->_gridOptions['massactionfield'] = 'id';
        $this->_gridOptions['routeName'] = $this->getFrontController()->getRouter()->getCurrentRouteName();    
        
        $this->view->gridOptions = $this->_gridOptions;
    }

    protected function _filterDataForCRUD($data)
    {
        $filtered = array();
        foreach($data as $key => $value){
            if(in_array($key, $this->_nullColumns) && empty($value)){
                $filtered[$key] = null;
            } else {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }

    public function createAction()
    {
        $this->view->title = "Tworzenie nowego zamówienia";
        $model = new Application_Model_DbTable_Order();
        $nextInvoiceId = $model->getNextInvoiceId();
        $form = new Application_Form_Order();
        $form->setDefault('unique', $nextInvoiceId);
//        $form->setDefault('status', 'other');

        if($this->getRequest()->isPost()){
            if($form->isValid($this->getRequest()->getPost())){
                $data = $form->getValues();
                $data = $this->_filterDataForCRUD($data);

                $id = $model->insert($data);
                return $this->_helper->redirector(
                        'update', 'order', null, array('order_id' => $id));
            }
        }

        $this->view->form = $form;
        
    }
    
    protected function saveRequestBeforeUpdate()
    {
        if(!$this->getRequest()->isXmlHttpRequest() && $this->getRequest()->getActionName() != 'update'){
            $this->_session->lastRequestParams = $this->getRequest()->getParams();
            $this->_session->lastRouteName = $this->getFrontController()->getRouter()->getCurrentRouteName();
        }
    }
    
    protected function loadSavedRequestUrl()
    {
        $result = null;
        if(!empty($this->_session->lastRouteName) && 
                !empty($this->_session->lastRequestParams)){
            $result = $this->_helper->url->url($this->_session->lastRequestParams, $this->_session->lastRouteName);
        }
        
        return $result;
    }

    public function updateAction()
    {        
        $id = $this->getRequest()->getParam('order_id');
        $this->view->title = "Edycja zamówienia <b>(id: {$id})</b>";
        
        $model = new Application_Model_DbTable_Order();
        $form = new Application_Form_Order;

        try{
            /* @var $orders Zend_Db_Table_Rowset */
            $orders = $model->find($id);
            if($orders->count()){
                $order = $orders->current();
                if($this->getRequest()->isPost()){
                    if($form->isValid($this->getRequest()->getPost())){
                        $data = $form->getValues();
                        $data['update_time'] = new Zend_Db_Expr('NOW()');
                        
                        $data = $this->_filterDataForCRUD($data);
                        
                        $order->setFromArray($data);
                        $order->save();
                        $this->_flashMessenger->setNamespace('info')->addMessage("<span class=\"success\">Zaktualizowano rekord!</span>");
                        $url = $this->_helper->url->url(array(), 'order_list');
                        $savedUrl = $this->loadSavedRequestUrl();
                        
                        if(!is_null($savedUrl)){
                            $url = $savedUrl;
                        }
                        
                        return $this->_redirect($url);
                    }
                }
                
                $order = $order->toArray();
                $this->view->form = $form->populate($order);
                $this->view->order = $order;
            } else {
                throw new Zend_Controller_Action_Exception(sprintf('Rekord o id "%s" nie istnieje', $id), 404);
            }
        } catch(Exception $e){
            throw $e;
        }

        $this->view->order_id = $id;
    }

    public function listAction()
    {
        $this->view->title = "Wylistowanie zamówień";
        $Order = new Application_Model_DbTable_Order;
        
        $page = $this->getRequest()->getParam('page', 1);
        $sort = $this->getRequest()->getParam('sort', 'date_of_payment');
        $dir = $this->getRequest()->getParam('dir', 'asc');
        $filtersData = $this->getFiltersData();
        $select = $Order->getUnpaidNotSentOrdersSelect($sort, $dir);
        $rowset = $Order->applyOrdersSelectFilters($filtersData, $select);
        
        $adapter = new Zend_Paginator_Adapter_Iterator($rowset);
        $paginator = new Zend_Paginator($adapter);
        
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->_gridOptions['dir'] = $dir;
        $this->_gridOptions['sort'] = $sort;
        $this->_gridOptions['routeName'] = 'order_list';
        $this->_gridOptions['paginator'] = $paginator;
        $this->view->gridOptions = $this->_gridOptions;
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('order_id');
        $this->view->title = "Usuwanie zamówienia <b>(id: {$id})</b>";
        
        $model = new Application_Model_DbTable_Order();
        $form = new Application_Form_DialogDelete;
        
        try{
            /* @var $orders Zend_Db_Table_Rowset */
            $orders = $model->find($id);
            if($orders->count()){
                $order = $orders->current();
                $this->view->actionMessage = sprintf("Chcesz usunąć zamówienie o nr %s?", $order->unique);
                
                if($this->getRequest()->isPost()){
                    if($form->isValid($this->getRequest()->getPost())){
                        $submit = $form->getValue('submit');
                        $cancel = $form->getValue('cancel');
                        
                        if(isset($submit)){
                            $order->delete();
                            $form = "";
                            $actionMessage = sprintf("<span class=\"success\">Usunięto zamówienie o nr %s!</span>", $order->unique);
                            $this->_flashMessenger->setNamespace('info')->addMessage($actionMessage);
                            $url = $this->_helper->url->url(array(), 'order_list');
                            if(!empty($this->_session->lastRouteName) && 
                                    !empty($this->_session->lastRequestParams)){
                                $url = $this->_helper->url->url($this->_session->lastRequestParams, $this->_session->lastRouteName);
                            }
                            return $this->_redirect($url);
                        } else if($cancel){
                            $this->_flashMessenger->setNamespace('info')->addMessage('Anulowano');
                            $url = $this->_helper->url->url(array(), 'order_list');
                            if(!empty($this->_session->lastRouteName) && 
                                    !empty($this->_session->lastRequestParams)){
                                $url = $this->_helper->url->url($this->_session->lastRequestParams, $this->_session->lastRouteName);
                            }
                            return $this->_redirect($url);
                        }
                    }
                }
                
            } else {
                throw new Zend_Controller_Action_Exception(sprintf('Rekord o id "%s" nie istnieje', $id), 404);
            }
        } catch(Exception $e){
            throw $e;
        }
        
        $this->view->form = $form;
    }
    
    protected function getFiltersData()
    {
        $filters = $this->getRequest()->getParam('filter', array());
        $notNullFilters = array();
        foreach($filters as $key => $value){
            if(!empty($value)){
                $notNullFilters[$key] = $value;
            }
        }
        return $notNullFilters;
    }

    protected function _exportToPdf()
    {
        if($this->getRequest()->isPost()){
            
            $selected = $this->getRequest()->getPost('selected', null);
            if(!is_null($selected)){
                $orderModel = new Application_Model_DbTable_Order();
                $values = array_values($selected);
//                $selected = array_reverse($selected);
                $select = $orderModel->select()->where('id IN (?)', $values);
                $orders = $orderModel->fetchAll($select);
                $this->view->assign(array('orders' => $orders));
                
                $this->_helper->layout->setLayout('pdf');
                $this->_helper->layout->disableLayout();
                
                $this->_helper->viewRenderer->setRender('pdf');
                $this->_helper->viewRenderer->setNoRender();
                
                $layout = $this->_helper->layout->getLayoutInstance();
                $layout->assign('content', $this->view->render('order/pdf.phtml'));
                $output = $layout->render();
                
                $stylesheet = file_get_contents(APPLICATION_PATH . "/../public/css/style.css");
                
                require_once(APPLICATION_PATH . "/../library/MPDF54/mpdf.php");
                $date = date('YmdHis');
                $name = "eksport_{$date}.pdf";
                $mpdf = new mPDF('utf-8', 'A4-L');
				$mpdf->simpleTables = true;
				$this->packTableData = true;
				$this->cacheTables = true;
                $mpdf->AddPage('L');
//                $mpdf = new mPDF(); 
                $mpdf->WriteHTML($output);
                $mpdf->Output($name, 'I');
//                $this->getRequest()->clearParams();
                
                exit;
            }
        }
        
    }

    public function listPaidAction()
    {
        $Order = new Application_Model_DbTable_Order;
        
        $page = $this->getRequest()->getParam('page', 1);
        $sort = $this->getRequest()->getParam('sort', 'date_of_payment');
        $dir = $this->getRequest()->getParam('dir', 'asc');
        $filtersData = $this->getFiltersData();
        $select = $Order->getPaidNotSentOrdersSelect($sort, $dir);
        $rowset = $Order->applyOrdersSelectFilters($filtersData, $select);
        
        $adapter = new Zend_Paginator_Adapter_Iterator($rowset);
        $paginator = new Zend_Paginator($adapter);
        
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        
        $this->_gridOptions['routeName'] = $this->_currentRouteName;
        $this->_gridOptions['paginator'] = $paginator;
        $this->_gridOptions['dir'] = $dir;
        $this->_gridOptions['sort'] = $sort;
        $this->view->gridOptions = $this->_gridOptions;
    }

    public function listUnpaidAction()
    {
        $Order = new Application_Model_DbTable_Order;
        
        $page = $this->getRequest()->getParam('page', 1);
        $sort = $this->getRequest()->getParam('sort', 'date_of_payment');
        $dir = $this->getRequest()->getParam('dir', 'asc');
        $filtersData = $this->getFiltersData();
        $select = $Order->getUnpaidOrdersSelect($sort, $dir);
        $rowset = $Order->applyOrdersSelectFilters($filtersData, $select);
        
        $adapter = new Zend_Paginator_Adapter_Iterator($rowset);
        $paginator = new Zend_Paginator($adapter);
        
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->_gridOptions['routeName'] = $this->_currentRouteName;
        $this->_gridOptions['paginator'] = $paginator;
        $this->_gridOptions['dir'] = $dir;
        $this->_gridOptions['sort'] = $sort;
        $this->view->gridOptions = $this->_gridOptions;
    }

    public function listSentAction()
    {
        $Order = new Application_Model_DbTable_Order;
        
        $page = $this->getRequest()->getParam('page', 1);
        $sort = $this->getRequest()->getParam('sort', 'date_of_payment');
        $dir = $this->getRequest()->getParam('dir', 'asc');
        $filtersData = $this->getFiltersData();
        $select = $Order->getPaidSentOrdersSelect($sort, $dir);
        $rowset = $Order->applyOrdersSelectFilters($filtersData, $select);
        
        $adapter = new Zend_Paginator_Adapter_Iterator($rowset);
        $paginator = new Zend_Paginator($adapter);
        
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->_gridOptions['routeName'] = $this->_currentRouteName;
        $this->_gridOptions['paginator'] = $paginator;
        $this->_gridOptions['dir'] = $dir;
        $this->_gridOptions['sort'] = $sort;
        $this->view->gridOptions = $this->_gridOptions;
    }
    
    public function ordersReceivedTomorrowAction()
    {
        $Order = new Application_Model_DbTable_Order;
        $date = date('Y-m-d', strtotime('+1 days'));
        $this->view->orders = $Order->getOrdersReceivedOn($date);
        $this->view->receivementDate = $date;
    }
}

















