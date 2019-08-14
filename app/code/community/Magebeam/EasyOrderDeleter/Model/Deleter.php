<?php
/**
 * Magebeam Easy Order Deleter deleter
 *
 * @category    Magebeam
 * @package     Magebeam_EasyOrderDeleter
 * @copyright   Copyright (c) 2012 Magebeam (http://www.magebeam.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magebeam_EasyOrderDeleter_Model_Deleter extends Mage_Core_Model_Abstract
{
    /**
     * Read connection
     *
     * @var Varien_Db_Adapter_Interface
     */
    protected $_readConn = null;

    /**
     * Write connection
     *
     * @var Varien_Db_Adapter_Interface
     */
    protected $_writeConn = null;

    /**
     * Existing database tables list
     *
     * @var array
     */
    protected $_existingTables = array();

    /**
     * Fetches list of existing tables from database
     */
    protected function _fetchExistingDatabaseTables()
    {
        $query = 'SHOW TABLES';
        $this->_existingTables = $this->_readConn->fetchCol($query);
    }

    /**
     * Deletes order and all its data from database
     *
     * @param int $orderId Order id to delete
     *
     * @return bool True if order successfully deleted, false otherwise
     */
    public function deleteOrder($orderId)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order->getId()) {
            return false;
        }
        $this->_initResources();
        $this->_deleteOrderCreditmemos($order);
        $this->_deleteOrderInvoices($order);
        $this->_deleteOrderQuotes($order);
        $this->_deleteOrderShipments($order);
        $this->_deleteOrderDownloadableLinksPurchased($order);
        $this->_deleteOrderData($order);
        $order = Mage::getModel('sales/order')->load($orderId);
        return !$order->getId();
    }

    /**
     * Initializes resources
     */
    protected function _initResources()
    {
        $this->_readConn = Mage::getSingleton('core/resource')->getConnection('read');
        $this->_writeConn = Mage::getSingleton('core/resource')->getConnection('write');
        $this->_fetchExistingDatabaseTables();
    }

    /**
     * Deletes order creditmemos
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderCreditmemos($order)
    {
        $tblSalesFlatCreditmemo = $this->_getTableName('sales_flat_creditmemo');
        if ($this->_isTableExists($tblSalesFlatCreditmemo)) {
            $tblSalesFlatCreditmemo = $this->_quoteIdentifier($tblSalesFlatCreditmemo);
            $where = $this->_quoteInto(
                "parent_id IN (SELECT entity_id FROM {$tblSalesFlatCreditmemo} WHERE order_id = ?)",
                $order->getId()
            );
            $this->_deleteIfTableExists('sales_flat_creditmemo_comment', $where);
            $this->_deleteIfTableExists('sales_flat_creditmemo_item', $where);
            $where = $this->_quoteInto(
                'order_id = ?',
                $order->getId()
            );
            $this->_delete('sales_flat_creditmemo_grid', $where);
            $this->_delete('sales_flat_creditmemo', $where);
        }
    }

    /**
     * Deletes order invoices
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderInvoices($order)
    {
        $tblSalesFlatInvoice = $this->_getTableName('sales_flat_invoice');
        if ($this->_isTableExists($tblSalesFlatInvoice)) {
            $tblSalesFlatInvoice = $this->_quoteIdentifier($tblSalesFlatInvoice);
            $where = $this->_quoteInto(
                "parent_id IN (SELECT entity_id FROM {$tblSalesFlatInvoice} WHERE order_id = ?)",
                $order->getId()
            );
            $this->_deleteIfTableExists('sales_flat_invoice_comment', $where);
            $this->_deleteIfTableExists('sales_flat_invoice_item', $where);
            $where = $this->_quoteInto(
                'order_id = ?',
                $order->getId()
            );
            $this->_delete('sales_flat_invoice_grid', $where);
            $this->_delete('sales_flat_invoice', $where);
        }
    }

    /**
     * Deletes order quotes
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderQuotes($order)
    {
        $tblSalesFlatOrder = $this->_getTableName('sales_flat_order');
        $select = $this->_readConn->select()
            ->from($tblSalesFlatOrder)
            ->columns('quote_id')
            ->where('entity_id = ?', $order->getId());
        $orderQuoteId = $this->_readConn->fetchOne($select);
        if ($orderQuoteId) {
            $tblSalesFlatQuoteAddress = $this->_getTableName('sales_flat_quote_address');
            if ($this->_isTableExists($tblSalesFlatQuoteAddress)) {
                $tblSalesFlatQuoteAddress = $this->_quoteIdentifier($tblSalesFlatQuoteAddress);
                $where = $this->_quoteInto(
                    "parent_item_id IN (SELECT address_id FROM {$tblSalesFlatQuoteAddress} WHERE quote_id = ?)",
                    $orderQuoteId
                );
                $this->_deleteIfTableExists('sales_flat_quote_address_item', $where);
                $where = $this->_quoteInto(
                    "address_id IN (SELECT address_id FROM {$tblSalesFlatQuoteAddress} WHERE quote_id = ?)",
                    $orderQuoteId
                );
                $this->_deleteIfTableExists('sales_flat_quote_shipping_rate', $where);
            }
            $tblSalesFlatQuoteItem = $this->_getTableName('sales_flat_quote_item');
            if ($this->_isTableExists($tblSalesFlatQuoteItem)) {
                $tblSalesFlatQuoteItem = $this->_quoteIdentifier($tblSalesFlatQuoteItem);
                $where = $this->_quoteInto(
                    "item_id IN (SELECT item_id FROM {$tblSalesFlatQuoteItem} WHERE quote_id = ?)",
                    $orderQuoteId
                );

                $this->_deleteIfTableExists('sales_flat_quote_item_option', $where);
            }
            $where = $this->_quoteInto(
                'quote_id = ?',
                $orderQuoteId
            );
            $this->_deleteIfTableExists('sales_flat_quote_address', $where);
            $this->_deleteIfTableExists('sales_flat_quote_item', $where);
            $this->_deleteIfTableExists('sales_flat_quote_payment', $where);
            $where = $this->_quoteInto(
               'quote_id = ?',
                $orderQuoteId
            );
            $this->_deleteIfTableExists('log_quote', $where);
            $where = $this->_quoteInto(
                'entity_id = ?',
                $orderQuoteId
            );
            $this->_deleteIfTableExists('sales_flat_quote', $where);
        }
    }

    /**
     * Deletes order shipments
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderShipments($order)
    {
        $tblSalesFlatShipment = $this->_getTableName('sales_flat_shipment');
        if ($this->_isTableExists($tblSalesFlatShipment)) {
            $tblSalesFlatShipment = $this->_quoteIdentifier($tblSalesFlatShipment);
            $where = $this->_quoteInto(
                "parent_id IN (SELECT entity_id FROM {$tblSalesFlatShipment} WHERE order_id = ?)",
                $order->getId()
            );
            $this->_deleteIfTableExists('sales_flat_shipment_comment', $where);
            $this->_deleteIfTableExists('sales_flat_shipment_item', $where);
        }
        $where = $this->_quoteInto(
            "order_id IN (SELECT entity_id FROM {$tblSalesFlatShipment} WHERE order_id = ?)",
            $order->getId()
        );
        $this->_deleteIfTableExists('sales_flat_shipment_track', $where);
        $where = $this->_quoteInto(
            'order_id = ?',
            $order->getId()
        );
        $this->_deleteIfTableExists('sales_flat_shipment_grid', $where);
        $this->_deleteIfTableExists('sales_flat_shipment', $where);
    }

    /**
     * Deletes downloadable items
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderDownloadableLinksPurchased($order)
    {
        $tblDownloadableLinkPurchased = $this->_getTableName('downloadable_link_purchased');
        if ($this->_isTableExists($tblDownloadableLinkPurchased)) {
            $tblDownloadableLinkPurchased = $this->_quoteIdentifier($tblDownloadableLinkPurchased);
            $where = $this->_quoteInto(
                "purchased_id IN (SELECT purchased_id FROM {$tblDownloadableLinkPurchased} WHERE order_id = ?)",
                $order->getId()
            );
            $this->_deleteIfTableExists('downloadable_link_purchased_item', $where);
        }
        $where = $this->_quoteInto(
            'order_id = ?',
            $order->getId()
        );
        $this->_deleteIfTableExists('downloadable_link_purchased', $where);
    }

    /**
     * Deletes order data
     *
     * @param Mage_Sales_Model_Order $order Order
     */
    protected function _deleteOrderData($order)
    {
        $where = $this->_quoteInto(
            'parent_id = ?',
            $order->getId()
        );
        $this->_deleteIfTableExists('sales_flat_order_address', $where);
        $this->_deleteIfTableExists('sales_flat_order_payment', $where);
        $this->_deleteIfTableExists('sales_flat_order_status_history', $where);
        $where = $this->_quoteInto(
            'order_id = ?',
            $order->getId()
        );

        // Delete order
        $this->_deleteIfTableExists('sales_flat_order_item', $where);
        if ($order->getIncrementId()) {
            $where = $this->_quoteInto(
                'increment_id = ?',
                $order->getIncrementId()
            );
            $this->_deleteIfTableExists('sales_flat_order_grid', $where);
        }
        $where = $this->_quoteInto(
            'entity_id = ?',
            $order->getId()
        );
        $this->_deleteIfTableExists('sales_flat_order', $where);
    }

    /**
     * Returns table name in database
     *
     * @param string $tableName Table name
     *
     * @return string
     */
    protected function _getTableName($tableName)
    {
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    /**
     * Returns quoted identifier
     *
     * @param string $identifier Identifier
     *
     * @return string
     */
    protected function _quoteIdentifier($identifier)
    {
        return $this->_readConn->quoteIdentifier($identifier);
    }

    /**
     * Returns SQL with value quoted into
     *
     * @param string $sql SQL string
     * @param string $value Value
     *
     * @return string
     */
    protected function _quoteInto($sql, $value)
    {
        return $this->_readConn->quoteInto($sql, $value);
    }

    /**
     * Returns boolean flag: true if table exists in database, false if not
     *
     * @param string $tableName Table name
     *
     * @return bool
     */
    protected function _isTableExists($tableName)
    {
        return in_array($tableName, $this->_existingTables);
    }

    /**
     * Executes DELETE SQL if table exists
     *
     * @param string $tableName Table name
     * @param string $where WHERE SQL clause
     */
    protected function _deleteIfTableExists($tableName, $where)
    {
        $fullTableName = $this->_getTableName($tableName);
        if (!$this->_isTableExists($fullTableName)) {
            return;
        }
        $this->_writeConn->delete($fullTableName, $where);
    }

    /**
     * Executes DELETE SQL
     *
     * @param string $tableName Table name
     * @param string $where WHERE SQL clause
     */
    protected function _delete($tableName, $where)
    {
        $fullTableName = $this->_getTableName($tableName);
        $this->_writeConn->delete($fullTableName, $where);
    }
}