<?php
/**
 * Magebeam Easy Order Deleter observer
 *
 * @category    Magebeam
 * @package     Magebeam_EasyOrderDeleter
 * @copyright   Copyright (c) 2012 Magebeam (http://www.magebeam.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magebeam_EasyOrderDeleter_Model_Observer
{
    /**
     * Adds "Delete" button to order view page
     *
     * @param Varien_Event_Observer $event Event object
     *
     * @return Magebeam_EasyOrderDeleter_Model_Observer
     */
    public function addDeleteOrderButton(Varien_Event_Observer $event)
    {
        /** @var Mage_Adminhtml_Block_Sales_Order_View $block */
        $block = $event->getBlock();
        if ($block->getId() != 'sales_order_view') {
            return $this;
        }
        $this->_addDeleteOrderButton($block);
        return $this;
    }

    /**
     * Adds "Delete" button to order view page
     *
     * @param Mage_Adminhtml_Block_Sales_Order_View $orderViewBlock Order view block
     *
     * @return Magebeam_EasyOrderDeleter_Model_Observer
     *
     */
    protected function _addDeleteOrderButton($orderViewBlock)
    {
        $order = $orderViewBlock->getOrder();
        if (!$order->getId()) {
            return $this;
        }
        if (Mage::helper('magebeam_easyorderdeleter')->isAllowedOrderDeleteAction()) {
            $orderViewBlock->addButton('delete', array(
                'label'     => Mage::helper('adminhtml')->__('Delete'),
                'class'     => 'delete',
                'onclick'   => 'deleteConfirm(\''. Mage::helper('adminhtml')->__('Are you sure you want to do this?')
                    .'\', \'' . $orderViewBlock->getUrl('*/*/delete', array('order_id' => $order->getId())) . '\')',
            ));
        }
        return $this;
    }
}