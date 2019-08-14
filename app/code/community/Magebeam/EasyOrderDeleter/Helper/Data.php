<?php
/**
 * Magebeam Easy Order Deleter data helper
 *
 * @category    Magebeam
 * @package     Magebeam_EasyOrderDeleter
 * @copyright   Copyright (c) 2012 Magebeam (http://www.magebeam.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magebeam_EasyOrderDeleter_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns boolean flag: true if "delete" action is allowed for order, false if not
     *
     * @return bool
     */
    public function isAllowedOrderDeleteAction()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/delete');
    }

    /**
     * Deletes order
     *
     * @param int $orderId Order id
     *
     * @return bool True if order has been deleted, false otherwise
     */
    public function deleteOrder($orderId)
    {
        $deleter = Mage::getSingleton('magebeam_easyorderdeleter/deleter');
        return $deleter->deleteOrder($orderId);
    }
}