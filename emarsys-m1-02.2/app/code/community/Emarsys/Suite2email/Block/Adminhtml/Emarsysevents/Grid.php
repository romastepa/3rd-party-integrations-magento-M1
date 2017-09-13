<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Block_Adminhtml_Emarsysevents_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('emarsyseventsGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setFilterVisibility(false);
    }

    /**
     * Init Emarsys event collection
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        try {
            $storeId = $this->getRequest()->getParam('store');
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()->addFieldToFilter('website_id',array('eq'=>$websiteId));
            $this->setCollection($collection);
            return parent::_prepareCollection();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * @return string
     */
    public function getMainButtonsHtml()
    {
        try {
            $html = parent::getMainButtonsHtml();
            $storeId = $this->getRequest()->getParam('store');
            $addButton = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('adminhtml')->__('Update Schema'),
                    'onclick' => "setLocation('" . $this->getUrl('*/*/updateSchema', array('store'=>$storeId)) . "')",
                    'class' => 'task'
                ))->toHtml();

            return $addButton . $html;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Configuration of Emarsys events grid
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        try {
            $this->addColumn('event_id', array(
                'header' => Mage::helper('suite2email')->__('Emarsys Event ID'),
                'align' => 'left',
                'index' => 'event_id',
                'width' => '50px',
                'filter' => false,
                'sortable' => false
            ));

            $this->addColumn('emarsys_event', array(
                'header' => Mage::helper('suite2email')->__('Emarsys Event'),
                'align' => 'left',
                'index' => 'emarsys_event',
                'width' => '50px',
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
