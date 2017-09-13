<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Eventmapping_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->setId('eventmappingGrid');
            $this->setSaveParametersInSession(true);
            $this->setFilterVisibility(false);

            $storeId = Mage::app()->getRequest()->getParam('store');
            if ($storeId == 0 || $storeId == NULL) {
                $storeId = Mage::helper('suite2email')->getDefaultStoreID();
            }

            Mage::getSingleton('core/session')->setData('gridData', '');
            $collection = Mage::getModel('suite2email/emarsyseventsmapping')->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            $gridArray = array();
            foreach ($collection as $col) {
                $gridArray[$col->getData("id")]['magento_event_id'] = $col->getData('magento_event_id');
                $gridArray[$col->getData("id")]['emarsys_event_id'] = $col->getData('emarsys_event_id');
            }
            $this->setDefaultSort('id');
            $this->setDefaultDir('ASC');

            Mage::getSingleton('core/session')->setData('gridData', $gridArray);
            Mage::getSingleton('core/session')->setData('storeId', $storeId);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Adding save mapping,recommended mapping, cancel buttons based on the recommended flag
     * @return string
     */
    public function getMainButtonsHtml()
    {
        try {
            $html = parent::getMainButtonsHtml();
            $addButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Save Mapping'),
                    'onclick' => "setLocation('" . $this->getUrl('*/*/saveGrid') . "')",
                    'class' => 'task'
                ))->toHtml();

            $recommended = $this->getRequest()->getParam("recommended");
            $storeId = $this->getRequest()->getParam("store");
            $cancelButton = '';
            if (!isset($recommended) || $recommended == "") {
                $addButton .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('adminhtml')->__('Recommended Mapping'),
                        'onclick' => "mappingConfirmation('We are going to create Recommended Events in configured Emarsys account. It may take couple of minutes. Are you sure for it?'
                , '{$this->getUrl('*/*/recommendedMapping',array('store'=>$storeId))}')",
                        'class' => 'task',
                    ))->toHtml();
            } else {
                $cancelButton .= $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label' => Mage::helper('adminhtml')->__('Cancel'),
                        'onclick' => "setLocation('" . $this->getUrl('*/*/index') . "')",
                        'class' => 'task cancel'
                    ))->toHtml();
            }

            return $cancelButton . $addButton . $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Init collection of Event mapping grid
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        try {
            $storeId = Mage::app()->getRequest()->getParam('store');
            if ($storeId == 0 || $storeId == NULL) {
                $storeId = Mage::helper('suite2email')->getDefaultStoreID();
            }
            $model = Mage::getModel('suite2email/emarsyseventsmapping');
            $collection = $model->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            if (!$collection->getSize()) {
                Mage::getModel('suite2email/emarsyseventsmapping')->insertFirstime($storeId);
            }
            $collection = $model->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            $this->setCollection($collection);
            $this->setDefaultSort('id');
            $this->setDefaultDir('asc');
            return parent::_prepareCollection();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Configuration of event mapping grid
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        try {
            $recommended = $this->getRequest()->getParam("recommended");

            $this->addColumn('magento_event_id', array(
                'header' => Mage::helper('sales')->__('Magento Event'),
                'align' => 'left',
                'renderer' => 'Emarsys_Suite2email_Block_Adminhtml_Renderer_Magentoevent',
                'filter' => false,
                'sortable' => false
            ));

            if (isset($recommended) && $recommended != "") {
                $this->addColumn('emarsys_event_id', array(
                    'header' => Mage::helper('suite2email')->__('Emarsys Event'),
                    'align' => 'left',
                    'renderer' => 'Emarsys_Suite2email_Block_Adminhtml_Renderer_Emarsyseventmapping',
                    'filter' => false,
                    'sortable' => false
                ));
            } else {
                $this->addColumn('emarsys_event_id', array(
                    'header' => Mage::helper('suite2email')->__('Emarsys Event'),
                    'align' => 'left',
                    'renderer' => 'Emarsys_Suite2email_Block_Adminhtml_Renderer_Emarsysevent',
                    'filter' => false,
                    'sortable' => false
                ));
            }

            if (count(Mage::app()->getStores()) > 1) {
                $this->addColumn('store_id', array(
                    'header' => Mage::helper('suite2email')->__('Store View'),
                    'index' => 'store_id',
                    'type' => 'store',
                    'filter' => false,
                    'sortable' => false,
                    'filter_condition_callback' => array($this,
                        '_filterStoreCondition'),
                ));
            }
            return parent::_prepareColumns();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Adding Store filter
     * @param $collection
     * @param $column
     */
    protected function _filterStoreCondition($collection, $column)
    {
        try{
        if (count(Mage::app()->getStores() > 1)) {
            if (!$value = $column->getFilter()->getValue()) {
                return;
            }
            $this->getCollection()->addStoreFilter($value);
        }
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
