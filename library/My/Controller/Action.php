<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Action
 *
 * @author = "piter";
 */
class My_Controller_Action extends Zend_Controller_Action {
    
    /**
     * Flaga niedostepnosci API
     * @var type 
     */
    protected $_API_unavailable = false;
    
    /**
     * Klasa API do obslugi webservive pogody
     * @var type 
     */
    protected $_API_Weather = null;
    
    /**
     *  Helper flashmessager
     */
    protected $_flashMessenger = null;
    
    /**
     * Logger
     * @var type 
     */
    protected $_logger = null;
    
    /**
     * Przelacznik kontekstu (do przelaczania
     * formatu odpowiedzi w przypadku zadan AJAXowych)
     * @var type 
     */
    
    protected $_contextSwitch = null;
    /**
     * Alternatywny przelacznik kontekstu
     * @var Zend_ 
     */
    protected $_ajaxContext = null;
    
    /**
     * Sesja
     * @var Zend_Session_Namespace
     */
    protected $_session = null;
    
    /**
     * Sesja
     * @var Zend_Session_Namespace
     */
    protected $_config = null;
    
    /**
     * Cache 
     * UWAGA: problem z cachowaniem obiektow rekordu App...Row i
     * zestawów rekordów App...Rowset. Sprawdzić czy to się da w przyszlosci
     * w ogole zrealizowac.
     * @var Zend_Cache
     */
    protected $_cache = null;
    
    public function init()
    {
        $this->_contextSwitch = $this->_helper->getHelper('contextSwitch');
        $this->_ajaxContext = $this->_helper->getHelper('AjaxContext');
        $this->_logger = Zend_Registry::get('logger');
//        $this->_ajaxContext->addActionContext('index', 'html')
//                    ->addActionContext('get-cities', 'json')
//                    ->initContext();
        $this->_contextSwitch
             ->addActionContext('get-cities', 'json')
             ->addActionContext('get-city-weather', 'json')
                ->setAutoJsonSerialization(false)
                    ->initContext();
                
        $this->_flashMessenger =
            $this->_helper->getHelper('FlashMessenger');
        
        $this->initView();
        
        $this->_cache = Zend_Registry::get('cache');
        $this->_session = Zend_Registry::get('session');
        
        $this->_config = Zend_Registry::get('config');
    }
}

?>
