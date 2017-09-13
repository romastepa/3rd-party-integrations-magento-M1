<?php

/**
 *
 * @category   Webextend
 * @package    Emarsys_Webextend
 * @copyright  Copyright (c) 2017 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Webextend_Block_Adminhtml_Attributemapping_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->setId('attributemappingGrid');
            $this->setSaveParametersInSession(true);
            $this->setFilterVisibility(false);

            $storeId = Mage::app()->getRequest()->getParam('store');
            if ($storeId == 0 || $storeId == NULL) {
                $storeId = Mage::helper('suite2email')->getDefaultStoreID();
            }

            Mage::getSingleton('core/session')->setData('attributegridData', '');
            $collection = Mage::getModel('webextend/emarsysproductattributesmapping')->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            $gridArray = array();
            foreach ($collection as $col) {
                $gridArray[$col->getData("id")]['magentoAttributeCode'] = $col->getData('magento_attribute_code');
                $gridArray[$col->getData("id")]['magentoAttributeCodeLabel'] = $col->getData('magento_attribute_code_label');
                $gridArray[$col->getData("id")]['emarsysAttributeCodeId'] = $col->getData('emarsys_attribute_code_id');
            }
            $this->setDefaultSort('id');
            $this->setDefaultDir('ASC');
            Mage::getSingleton('core/session')->setData('attributegridData', $gridArray);
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
            $storeId = $this->getRequest()->getParam("store");

            $addNewAttributeButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Add Emarsys Attribute'),
                    'onclick' => "setLocation('" . $this->getUrl('*/webextend_newattribute', array('store' => $storeId)) . "')",
                    'class' => 'task'
                ))->toHtml();

            $addUpdateSchemaButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Update Schema'),
                    'onclick' => "setLocation('" . $this->getUrl('*/*/updateSchema', array('store' => $storeId)) . "')",
                    'class' => 'task'
                ))->toHtml();

            $addButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Save Mapping'),
                    'onclick' => "setLocation('" . $this->getUrl('*/*/saveGrid') . "')",
                    'class' => 'task'
                ))->toHtml();

            $cancelButton = '';
            $addButton .= $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Recommended Mapping'),
                    'onclick' => "mappingConfirmation('Do you want to change the current mapping to recommended mapping?', '{$this->getUrl('*/*/recommendedMapping',array('store'=>$storeId))}')",
                    'class' => 'task',
                ))->toHtml();

            return $addNewAttributeButton . $addUpdateSchemaButton . $cancelButton . $addButton . $html;
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
            $model = Mage::getModel('webextend/emarsysproductattributesmapping');
            $collection = $model->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            if (!$collection->getSize()) {
                Mage::getModel('webextend/emarsysproductattributesmapping')->insertFirstime($storeId);
            }
            $collection = $model->getCollection();
            $collection->addFieldToFilter("store_id", $storeId);
            $this->setCollection($collection);
            $this->setDefaultSort('magento_attribute_code');
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

            $this->addColumn('magento_attribute_code', array(
                'header' => Mage::helper('sales')->__('Magento Product Attribute'),
                'align' => 'left',
                'index' => 'magento_attribute_code_label',
                'filter' => false,
                'sortable' => false
            ));

            $this->addColumn('attribute_id', array(
                'header' => Mage::helper('suite2email')->__('Emarsys Attribute'),
                'align' => 'left',
                'renderer' => 'Emarsys_Webextend_Block_Adminhtml_Renderer_Emarsysproductattributes',
                'filter' => false,
                'sortable' => false
            ));

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
        try {
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
