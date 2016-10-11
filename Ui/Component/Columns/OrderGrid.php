<?php

/**
 * @author Matthew Cooper
 * @author Matthew Cooper <matthew.cooper@thedailyedited.com>
 */
namespace Tde\SalesGrid\Ui\Component\Columns;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderGrid extends Column
{

    /*
    * @var UiComponentInterface
    */
    protected $wrappedComponent;

    /**
     * UI component factory
     *
     * @var UiComponentFactory
     */
    protected $uiComponentFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $readResource;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ResourceConnection $resourceConnection,
        array $components = [],
        array $data = []
    )
    {
        $this->uiComponentFactory = $uiComponentFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->readResource = ObjectManager::getInstance()->create('Magento\Framework\App\ResourceConnection')->getConnection('core_read');
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$order) {
                $sql = 'SELECT GROUP_CONCAT(sku) skus FROM sales_order_item WHERE order_id = ' . $order['entity_id'] . ' AND product_type = "simple"';
                $products = $this->readResource->fetchRow($sql);
                $skus = '';
                foreach (array_count_values(explode(',', $products['skus'])) as $sku => $qty) {
                    $skus .= $sku . ' x ' . $qty . '<br>';
                }
                $order['skus'] = $skus;
            }
        }
        return $dataSource;
    }
}
