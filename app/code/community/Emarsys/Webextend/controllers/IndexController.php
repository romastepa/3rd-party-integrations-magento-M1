<?php

class Emarsys_Webextend_IndexController extends Mage_Core_Controller_Front_Action
{    
    /**
     * Action for ajax update webextend
     */
    public function ajaxUpdateAction()
    {
        $params = $this->getRequest()->getParam('unique_key');
        $result = array();
        $result['content'] = '';
        try {
            $this->loadLayout();
            $result['content'] = $this->getLayout()->createBlock('webextend/webextend')
                ->setTemplate('emarsys/javascripttracking.phtml')
                ->toHtml();
            $result['status'] = 1;
        } catch (Exception $e) {
            $result['status'] = 0;
            Mage::helper('emarsys_suite2')->log('WebExtend_ajaxUpdateAction_Exception: ' . $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
