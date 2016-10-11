<?php

/**
 * @author Matthew Cooper
 * @author Matthew Cooper <matthew.cooper@thedailyedited.com>
 */
namespace Tde\SalesGrid\Model\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'sales_order_grid',
        $resourceModel = '\Magento\Sales\Model\ResourceModel\Order'
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Get SQL for get record count
     *
     * @return Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();
        $select = clone $this->getSelect();
        $select->reset(Select::ORDER);
        $where = $select->getPart(Select::WHERE);
        $updatedWhere = [];
        $skuClause = [];
        foreach ($where as $k => $condition) {
            if (stripos($condition, '`skus`') !== false) {
                $skuClause[] = $condition;
            } else {
                $updatedWhere[] = $condition;
            }
        }
        if (empty($skuClause)) {
            return parent::getSelectCountSql();
        }
        //We only need the first condition, and we remove leading AND if present
        $skuClause = preg_replace('/`skus`/', '`soi`.`sku`', $skuClause[0]);
        if (empty($updatedWhere)) {
            $skuClause = preg_replace('/^AND /', '', $skuClause);
        }
        $updatedWhere[] = $skuClause;
        $select->setPart(Select::WHERE, $updatedWhere);
        $this->_select = $select;
        return parent::getSelectCountSql();
    }

    /**
     * Filter by sku compensation
     */
    protected function _renderFiltersBefore()
    {
        $salesOrderJoinTable = $this->getTable('sales_order');
        $salesOrderItemJoinTable = $this->getTable('sales_order_item');
        $shippingAddressTable = $this->getTable('sales_order_address');
        $this->getSelect()->reset(Select::ORDER);
        $this->getSelect()->join($salesOrderJoinTable . ' as so', 'main_table.increment_id = so.increment_id', []);
        $this->getSelect()->join(
            $salesOrderItemJoinTable . ' as soi',
            'so.entity_id = soi.order_id AND soi.product_type IN ("simple", "virtual", "giftcard")',
            array('skus' => 'GROUP_CONCAT(soi.sku)')
        );
        $this->getSelect()->joinLeft(
            $shippingAddressTable . ' as soa',
            'so.entity_id = soa.parent_id AND soa.address_type = "shipping"',
            ['country_id']
        );
        //deal with sales_order and sales+order_grid ambiguity on where clause
        $commonFields = [
            'entity_id',
            'status',
            'store_id',
            'store_name',
            'customer_id',
            'base_grand_total',
            'base_total_paid',
            'grand_total',
            'total_paid',
            'increment_id',
            'base_currency_code',
            'order_currency_code',
            'created_at',
            'updated_at',
            'customer_email',
            'subtotal',
            'total_refunded'
        ];
        $where = $this->getSelect()->getPart(Select::WHERE);
        $this->getSelect()->reset(Select::WHERE);
        foreach ($where as &$condition) {
            foreach ($commonFields as $field) {
                $condition = preg_replace('/`' . $field . '`/', '`main_table`.`' . $field . '`', $condition);
            }

        }
        $this->getSelect()->setPart(Select::WHERE, $where);
        $this->getSelect()->group('main_table.entity_id');
        parent::_renderFiltersBefore();
    }
}
