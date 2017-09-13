<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Magentoevents_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('magentoeventsGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(false);
    }

    /**
     * Init collection of Magento events
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        try {
            Mage::getSingleton('core/session')->setData('eventStoreId', '');
            $storeId = $this->getRequest()->getParam('store');
            if ($storeId == 0 || $storeId == NULL) {
                $storeId = $storeId = Mage::helper('suite2email')->getDefaultStoreID();
            }
            Mage::getSingleton('core/session')->setData('eventStoreId', $storeId);
            $collection = Mage::getModel('suite2email/emarsysmagentoevents')->getCollection()->addFieldToFilter("id", array("nin" => Mage::helper("suite2email")->getReadonlyMagentoEventIds()));;
            $this->setCollection($collection);
            return parent::_prepareCollection();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Configuration of Magento events grid
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        try {
            $this->addColumn('magento_event', array(
                'header' => Mage::helper('sales')->__('Magento Event'),
                'align' => 'left',
                'index' => 'magento_event',
                'filter' => false,
                'sortable' => false
            ));

            $this->addColumn('config_path', array(
                'header' => Mage::helper('sales')->__('Configuration Path'),
                'align' => 'left',
                'index' => 'config_path',
                'filter' => false,
                'sortable' => false
            ));

            $this->addColumn('Template Code', array(
                'header' => Mage::helper('sales')->__('Magento Template'),
                'align' => 'left',
                'renderer' => 'Emarsys_Suite2email_Block_Adminhtml_Renderer_TemplateCode',
                'filter' => false,
                'sortable' => false
            ));
            return parent::_prepareColumns();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Overriding the core method to make it unclickable
     * @param $row
     * @return string|void
     */
    public function getRowUrl($row)
    {
    }
}
