<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Placeholders_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            parent::__construct();

            $this->setId('placeholdersGrid');
            $this->setDefaultSort('id');
            $this->setDefaultDir('ASC');
            $this->setSaveParametersInSession(true);
            $this->setFilterVisibility(false);
            $mapping_id = $this->getRequest()->getParam('mapping_id');
            $storeId = $this->getRequest()->getParam('store_id');

            $model = Mage::getModel('suite2email/emarsysplaceholdermapping');
            $collection = $model->getCollection()
                ->addFieldToFilter("store_id", $storeId)
                ->addFieldToFilter("event_mapping_id", $mapping_id);
            if (!$collection->getSize()) {
                Mage::getModel('suite2email/emarsysplaceholdermapping')->insertFirstime($mapping_id, $storeId);
            }

            Mage::getSingleton('core/session')->setData('gridPlaceholders', '');
            $collection = Mage::getModel('suite2email/emarsysplaceholdermapping')->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            $collection->addFieldToFilter("event_mapping_id", $mapping_id);
            $gridArray = array();
            foreach ($collection as $col) {
                $gridArray[$col->getData("id")]['event_mapping_id'] = $col->getData('event_mapping_id');
                $gridArray[$col->getData("id")]['magento_placeholder_name'] = $col->getData('magento_placeholder_name');
                $gridArray[$col->getData("id")]['emarsys_placeholder_name'] = $col->getData('emarsys_placeholder_name');
            }
            Mage::getSingleton('core/session')->setData('gridPlaceholders', $gridArray);
            Mage::getSingleton('core/session')->setData('mappingId', $mapping_id);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Init placeholder collection
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        try {
            $storeId = $this->getRequest()->getParam('store_id');
            $mapping_id = $this->getRequest()->getParam('mapping_id');
            $collection = Mage::getModel('suite2email/emarsysplaceholdermapping')->getCollection();
            $collection->addFieldToFilter("event_mapping_id", $mapping_id);
            $collection->addFieldToFilter("store_id", $storeId);
            $this->setCollection($collection);
            return parent::_prepareCollection();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Adding savemapping, refresh placeholders, back buttons to the grid
     * @return string
     */
    public function getMainButtonsHtml()
    {
        try {
            $html = parent::getMainButtonsHtml();
            $storeId = Mage::getSingleton('core/session')->getData('storeId');
            $mappingId = Mage::app()->getRequest()->getParam("mapping_id");
            $eventMappingUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_eventmapping/index", array('store' => $storeId));
            $refreshPlaceholdersUrl = Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_placeholders/refreshPlaceholders", array('store' => $storeId, 'mapping_id' => $mappingId));
            $addButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Back'),
                    'onclick' => 'setLocation(\'' . $eventMappingUrl . '\')',
                    'class' => 'task'
                ))->toHtml();

            $addButton .= $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Refresh Placeholders'),
                    'onclick' => 'setLocation(\'' . $refreshPlaceholdersUrl . '\')',
                    'class' => 'task'
                ))->toHtml();

            $addButton .= $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Save Mapping'),
                    'onclick' => "placeHolderValidation('" . $this->getUrl('*/*/saveMapping') . "');",
                    'class' => 'task'
                ))->toHtml();

            return $addButton . $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Configuration of placeholder grid
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        try {
            $this->addColumn('magento_placeholder_name', array(
                'header' => Mage::helper('sales')->__('Magento Variables'),
                'align' => 'left',
                'index' => 'magento_placeholder_name',
                'width' => '500px',
                'filter' => false,
                'sortable' => false
            ));

            $this->addColumn('emarsys_placeholder_name', array(
                'header' => Mage::helper('sales')->__('Emarsys Placeholders'),
                'align' => 'left',
                'renderer' => 'Emarsys_Suite2email_Block_Adminhtml_Renderer_Placeholders',
                'width' => '1px',
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

