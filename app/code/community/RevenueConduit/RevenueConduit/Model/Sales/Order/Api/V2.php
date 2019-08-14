<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order API
 *
 * @category   RevenueConduit
 * @package    RevenueConduit_RevenueConduit_Sales
 * @author     Parag Jagdale (Revenue Conduit)
 */
class RevenueConduit_RevenueConduit_Model_Sales_Order_Api_V2 extends Mage_Sales_Model_Order_Api
{

  public function order_count()
  {
    $_orders = Mage::getModel('sales/order')->getCollection();                        
    $_orderCnt = $_orders->count(); //orders count
    return $_orderCnt;
  }

  public function light_items($filters = null)
  {
  
    $start = 0;
    $count = FALSE;    
    foreach ($filters->complex_filter as $field => $condition) {
	if($condition->key == "start"){
	  $start = $condition->value->value;
	  unset($filters->complex_filter->field);
	}
	if($condition->key == "count"){
	  $count = $condition->value->value;
	  unset($filters->complex_filter->field);
	}	
    }
	      
        $orders = array();

        //TODO: add full name logic
        $billingAliasName = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';

        /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $orderCollection = Mage::getModel("sales/order")->getCollection()->setOrder('entity_id', 'ASC');

        $orderCollection->getSelect()
        ->reset(Zend_Db_Select::COLUMNS)
        ->columns('increment_id')
        ->columns('entity_id')
        ->columns('customer_id')
        ->columns('store_id')
        ->columns('created_at')
        ->columns('updated_at')
        ->columns('status')
        ->columns('state');


        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters, $this->_attributesMap['order']);
        try {
            foreach ($filters as $field => $value) {
		if($field == 'start' || $field == 'count') continue;
                $orderCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }

        foreach ($orderCollection as $order) {
	    if($start){
	      $start--;
	      continue;
	    }
            $orders[] = $this->_getAttributes($order, 'order');
            if($count !== FALSE && count($orders) == $count){
	      break;
            }
        }
        return $orders;
  }

    /**
     * Retrieve list of orders. Filtration could be applied
     *
     * @param null|object|array $filters
     * @return array
     */
    public function items($filters = null)
    {
        $orders = array();

        //TODO: add full name logic
        $billingAliasName = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';

        /** @var $orderCollection Mage_Sales_Model_Mysql4_Order_Collection */
        $orderCollection = Mage::getModel("sales/order")->getCollection();
        $billingFirstnameField = "$billingAliasName.firstname";
        $billingLastnameField = "$billingAliasName.lastname";
        $shippingFirstnameField = "$shippingAliasName.firstname";
        $shippingLastnameField = "$shippingAliasName.lastname";
        $orderCollection->addAttributeToSelect('*')
            ->addAddressFields()
            ->addExpressionFieldToSelect('billing_firstname', "{{billing_firstname}}",
                array('billing_firstname' => $billingFirstnameField))
            ->addExpressionFieldToSelect('billing_lastname', "{{billing_lastname}}",
                array('billing_lastname' => $billingLastnameField))
            ->addExpressionFieldToSelect('shipping_firstname', "{{shipping_firstname}}",
                array('shipping_firstname' => $shippingFirstnameField))
            ->addExpressionFieldToSelect('shipping_lastname', "{{shipping_lastname}}",
                array('shipping_lastname' => $shippingLastnameField))
            ->addExpressionFieldToSelect('billing_name', "CONCAT({{billing_firstname}}, ' ', {{billing_lastname}})",
                array('billing_firstname' => $billingFirstnameField, 'billing_lastname' => $billingLastnameField))
            ->addExpressionFieldToSelect('shipping_name', 'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
                array('shipping_firstname' => $shippingFirstnameField, 'shipping_lastname' => $shippingLastnameField)
        );

        /** @var $apiHelper Mage_Api_Helper_Data */
        $apiHelper = Mage::helper('api');
        $filters = $apiHelper->parseFilters($filters, $this->_attributesMap['order']);
        try {
            foreach ($filters as $field => $value) {
                $orderCollection->addFieldToFilter($field, $value);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        foreach ($orderCollection as $order) {
            $orders[] = $this->_getAttributes($order, 'order');
        }
        return $orders;
    }

    public function customer_count()
    {
      $_customers = Mage::getModel('customer/customer')->getCollection();                        
      $_customerCnt = $_customers->count(); //customers count
      return $_customerCnt;
    }    
    
    public function product_count()
    {
      $_products = Mage::getModel('catalog/product')->getCollection();                        
      $_productCnt = $_products->count(); //customers count
      return $_productCnt;
    } 
    
    public function product_category_count()
    {
      $_products_categories = Mage::getModel('catalog/category')->getCollection();                        
      $_productCategoryCnt = $_products_categories->count(); //customers count
      return $_productCategoryCnt;
    }
    public function customer_get_subscription_status($email){
      $_subscribers = Mage::getModel('newsletter/subscriber')->loadByEmail($email);

      return $_subscribers->isSubscribed();
    }
} // Class Mage_Sales_Model_Order_Api End
