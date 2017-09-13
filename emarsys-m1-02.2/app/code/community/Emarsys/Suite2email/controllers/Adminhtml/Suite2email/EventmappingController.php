<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */
class Emarsys_Suite2email_Adminhtml_Suite2email_EventmappingController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return $this
     */
    protected function _initAction()
    {
        try {
            $this->loadLayout()
                ->_setActiveMenu('suite2email/mappingitems')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Verify the permissions
     */
    protected function _isAllowed()
    {
        try {
            return Mage::getSingleton('admin/session')->isAllowed('suite2email/mappingitems');
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Index function
     */
    public function indexAction()
    {
        try {
            $storeId = $this->getRequest()->getParam('store');
            if(!$storeId){
                $storeId = Mage::getSingleton('suite2email/emarsysevents')->getFirstStoreId();
                Mage::app()->getResponse()->setRedirect($this->getUrl("adminhtml/suite2email_eventmapping/index/",array('store'=>$storeId)));
            }
            $this->_initAction()
                ->renderLayout();
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Save grid data to db
     */
    public function saveGridAction()
    {
        try {
            $model = Mage::getModel("suite2email/emarsyseventsmapping");
            $session = Mage::getSingleton("core/session")->getData();
            $storeId = $session['storeId'];
            $gridSessionData = $session['gridData'];
            foreach ($gridSessionData as $key => $value) {
                $model->setId($key);
                $model->setStoreId($storeId);
                $model->setMagentoEventId($gridSessionData[$key]['magento_event_id']);
                $model->setEmarsysEventId($gridSessionData[$key]['emarsys_event_id']);
                $model->save();
            }
            Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("Events mapped successfully!"));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_eventmapping/index/", array("store" => $storeId)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
            Mage::getSingleton("adminhtml/session")->addError(Mage::helper("adminhtml")->__("Error occurred while mapping Events"));
            $this->_redirect('*/*/index');
        }
    }

    /**
     * change grid coulmn value using ajax
     */
    public function changeValueAction()
    {
        try {
            $magento_event_id = $this->getRequest()->getParam('magentoeventId');
            $emarsys_event_id = $this->getRequest()->getParam('emarsyseventId');
            $id = $this->getRequest()->getParam('Id');
            $session = Mage::getSingleton("core/session")->getData();
            $gridSession = $session['gridData'];
            $gridSession[$id]['magento_event_id'] = $magento_event_id;
            $gridSession[$id]['emarsys_event_id'] = $emarsys_event_id;
            Mage::getSingleton('core/session')->setData('gridData', $gridSession);
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }

    /**
     * Recommended Mapping function
     */

    public function recommendedMappingAction()
    {
        try {
            // Pull events from Emarsys
            $storeId = $this->getRequest()->getParam('store');
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            Mage::getSingleton('emarsys_suite2/config')->setWebsite($websiteId);
            Mage::getModel("suite2email/emarsysevents")->importEvents($storeId);

            // Add new events in Emarsys if not exist
            $session = Mage::getSingleton("core/session")->getData();
            $collection = Mage::getModel('suite2email/emarsysevents')->getCollection()->addFieldToFilter('website_id',array('eq'=>$websiteId));
            $dbEvents = array();
            foreach ($collection as $_event) {
                $dbEvents[] = $_event->getEmarsysEvent();
            }

            $hasNewEvents = false;
            $events = Mage::getModel("suite2email/emarsysmagentoevents")->getCollection();
            foreach ($events as $event) {
                if (Mage::helper("suite2email")->isReadonlyMagentoEventId($event->getId())) {
                    continue;
                }
                $magentoEventname = $event->getMagentoEvent();
                $emarsysEventname = trim(str_replace(" ", "_", strtolower($magentoEventname)));
                if (!in_array($emarsysEventname, $dbEvents)) {
                    $data['name'] = $emarsysEventname;
                    $hasNewEvents = true;
                    try {
                        Mage::helper('emarsys_suite2')->getClient()->post('event', $data);
                    } catch (Exception $e) {
                        Mage::log($e->getMessage());
                    }
                }
            }

            if ($hasNewEvents) {
                // Pull events from Emarsys
                Mage::getModel("suite2email/emarsysevents")->importEvents($storeId);
            }

            $storeId = $session['storeId'];
            Mage::getSingleton("adminhtml/session")->addNotice(Mage::helper("adminhtml")->__("Recommended Emarsys Events Created Successfully!"));
            Mage::getSingleton("adminhtml/session")->addNotice(Mage::helper("adminhtml")->__('Important: Hit "Save Mapping" to complete the mapping!'));
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/suite2email_eventmapping/index/", array("store" => $storeId, "recommended" => 1, "limit" => 200)));
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}