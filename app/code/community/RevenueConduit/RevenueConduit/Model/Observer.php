<?php
/**
 * RevenueConduit
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA available
 * through the world-wide-web at this URL:
 * http://revenueconduit.com/magento/license
 *
 * MAGENTO EDITION USAGE NOTICE
 *
 * This package is designed for Magento COMMUNITY edition.
 * =================================================================
 *
 * @package    RevenueConduit
 * @copyright  Copyright (c) 2012-2013 RevenueConduit. (http://www.revenueconduit.com)
 * @license    http://revenueconduit.com/magento/license
 * @terms      http://revenueconduit.com/magento/terms
 * @author     Parag Jagdale
 */

class RevenueConduit_RevenueConduit_Model_Observer{

  var $_infusionsoftConnectionInfo = array();

  public function load(){
  }

  public function initialize($observer){

    if (!($observer->getEvent()->getBlock() instanceof Mage_Adminhtml_Block_Page)) {
      return;
    }
    return $this;
  }

  public function SendRequest($topic, $orderId, $customerId, $productId=0, $categoryId=0){
    $affiliate = 0;
    $client = new Varien_Http_Client();

    $company_app_name = $this->getStoreConfig('revenueconduit_app_name');
    $store_name = $this->getStoreConfig('revenueconduit_store_name');

    $storeId = Mage::app()->getStore()->getStoreId();
    $url = Mage::getStoreConfig('web/unsecure/base_link_url', $storeId);

    $host = "https://app.revenueconduit.com/magento_incoming_requests/receive";
    
    $parameter = array("company_app_name" => $company_app_name, "store_name" => $store_name, 'domain' => $url);

    if(!empty($_COOKIE) && array_key_exists('affiliate', $_COOKIE)){
	    $affiliate = $_COOKIE['affiliate'];
    }

    $postParameters = array("topic" => $topic, "order_id" => $orderId, "customer_id" => $customerId, "product_id" => $productId, 'category_id' => $categoryId, 'referral_code_id' => $affiliate ? $affiliate : 0);

    $client->setUri($host)
      ->setConfig(array('timeout' => 30))
      ->setParameterGet($parameter)
      ->setParameterPost($postParameters)
      ->setUrlEncodeBody(false)
      ->setMethod(Zend_Http_Client::POST);

    try {
      $response = $client->request();
      return $response->getBody();
    } catch (Exception $e) {
      Mage::log("Could not send the $topic request to RevenueConduit. Error Message: " . $e->getMessage(), null);
    }
    return null;
  }

  public function beforeSave($observer){
      try{
	$product = $observer->getEvent()->getProduct();
	if(!$product->getID())
	  $product->isNewProduct = true;

      }catch(Exception $ex){
        Mage::log("Could not send a product update request to RevenueConduit.", null);
      }

    return $this;
  }
  public function beforeCategorySave($observer){
      try{
	$category = $observer->getEvent()->getCategory();
	if(!$category->getID())
	  $category->isNewCategory = true;

      }catch(Exception $ex){
        Mage::log("Could not send a product update request to RevenueConduit.", null);
      }

    return $this;
  }

  public function beforeCustomerSave($observer){
      try{
	$customer = $observer->getEvent()->getCustomer();
	if(!$customer->getID())
	  $customer->isNewCustomer = true;

      }catch(Exception $ex){
        Mage::log("Could not send a product update request to RevenueConduit.", null);
      }

    return $this;
  }  
  public function UpdateProduct($observer){

      try{
	$product = $observer->getEvent()->getProduct();
	if ($product instanceof Mage_Catalog_Model_Product) {
	  if($product->isNewProduct)
	    $codeFromSite = $this->SendRequest("products/create", 0, 0, $product->getID());
	  else
	    $codeFromSite = $this->SendRequest("products/update", 0, 0, $product->getID());
        }
      }catch(Exception $ex){
        Mage::log("Could not send a product update request to RevenueConduit.", null);
      }

    return $this;
  }
  public function UpdateCategory($observer){

      try{
	$category = $observer->getEvent()->getCategory();
	if ($category instanceof Mage_Catalog_Model_Category) {
	  if($category->isNewCategory)
	    $codeFromSite = $this->SendRequest("categories/create", 0, 0, 0, $category->getID());
	  else
	    $codeFromSite = $this->SendRequest("categories/update", 0, 0, 0, $category->getID());
        }
      }catch(Exception $ex){
        Mage::log("Could not send a category update request to RevenueConduit.", null);
      }

    return $this;
  }
  public function UpdateCustomer($observer){

    $customer = $observer->getEvent()->getCustomer();
    if (($customer instanceof Mage_Customer_Model_Customer) && $customer->getId()) {
      try{
	if($customer->isNewCustomer)
	  $codeFromSite = $this->SendRequest("customers/create", 0, $customer->getId());
	else
	  $codeFromSite = $this->SendRequest("customers/update", 0, $customer->getId());
      }catch(Exception $ex){
        Mage::log("Could not send a customer created request to RevenueConduit.", null);
      }
    }
    return $this;
  }
  
  public function CreateContactRecord($observer){

    $customer = $observer->getEvent()->getCustomer();

    if (($customer instanceof Mage_Customer_Model_Customer)) {

      try{
        $codeFromSite = $this->SendRequest("customers/create", 0, $customer->getId());
      }catch(Exception $ex){
        Mage::log("Could not send a customer created request to RevenueConduit.", null);
      }
    }
    return $this;
  }

  public function AssignOrderSequenceOnCheckout($observer){

    $orderId = 0;
    $customerId = 0;

    $orderId = $observer->getOrder()->getRealOrderId();

    if(Mage::getSingleton('customer/session')->isLoggedIn()) {
        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
    }

    try{
            $codeFromSite = $this->SendRequest("orders/create", $orderId, $customerId);
    }catch(Exception $ex){
            Mage::log("Could not send an orders/create request to RevenueConduit. The order Id is: " .  $orderId, null);
    }
    return $this;
  }

  public function OnOrderUpdate($observer){
    try{
      $order = $observer->getEvent()->getInvoice()->getOrder();
      $codeFromSite = $this->SendRequest("orders/updated", $order->getIncrementId(), null);
    }catch(Exception $ex){
      Mage::log("Could not send an order created request to RevenueConduit. " . $ex->getMessage() , null);
    }
    return $this;
  }
  
    public function captureReferral(Varien_Event_Observer $observer)
    {
        // here we add the logic to capture the referring affiliate ID
	$frontController = $observer->getEvent()->getFront();        
	if(!empty($frontController)){
		$affiliateID = $frontController->getRequest()->getParam('affiliate', false);
		if(!empty($affiliateID)){
			setcookie("affiliate", $affiliateID, time()+3600);
		}
	}
    }  

  /*
   * Gets the data from the store configuration
   * @param string keyName - the string which is the key that identifies the value that is requested
   */
  public function getStoreConfig($keyName = null, $group = "revenueconduit_revenueconduit_settings_group"){
    if(!empty($keyName)){
      $value = Mage::getStoreConfig("revenueconduit_revenueconduit_options/$group/" . $keyName);
      if(!empty($value)){
        return trim($value);
      }else{
        return $value;
      }
    }else{
      return null;
    }
  }

  public function setStoreConfig($keyName, $value){
    Mage::getModel('core/config')->saveConfig('revenueconduit_revenueconduit_options/revenueconduit_revenueconduit_group/' . $keyName, $value );
  }

}
