<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap('db');

$xml = simplexml_load_file('../data/orders.xml');
$orderModel = new Application_Model_DbTable_Order();
$orderModel->delete('');

foreach($xml->order as $order){
//    $data = array(
//            'unique' => $order->unique,
//            'model' => $order->model,
//            'color' => $order->color,
//            'is_seat' => $order->isseat,
//            'frame' => $order->frame,
//            'bonus' => $order->bonus,
//            'wheels' => $order->wheels,
//            'client' => $order->client,
//            'date_of_receipt' => $order->dateofreceipt
//    );
    $data = (array) $order;
    
    try{
        $orderModel->createRow($data)->save();
    } catch(Zend_Db_Exception $e){
        echo $e->getMessage();
    }
}