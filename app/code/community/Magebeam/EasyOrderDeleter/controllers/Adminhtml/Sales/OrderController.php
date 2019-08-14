<?php
/**
 * Magebeam Easy Order Deleter order controller
 *
 * @category    Magebeam
 * @package     Magebeam_EasyOrderDeleter
 * @copyright   Copyright (c) 2012 Magebeam (http://www.magebeam.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magebeam_EasyOrderDeleter_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Order id param name
     */
    const PARAM_ORDER_ID = 'order_id';

    /**
     * Delete order action
     */
    public function deleteAction()
    {
        $orderId = $this->getRequest()->getParam(self::PARAM_ORDER_ID);
        $isOrderDeleted = Mage::helper('magebeam_easyorderdeleter')->deleteOrder($orderId);
        if ($isOrderDeleted) {
            $this->_getSession()->addSuccess(
                $this->__('The order has been deleted.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('sales')->__('This order no longer exists.')
            );
        }
        $this->_redirect('*/sales_order/index');
    }
}