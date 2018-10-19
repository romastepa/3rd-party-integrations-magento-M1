<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of Abstract
 *
 * @author Vo1
 */
class Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Abstract extends Varien_Data_Form
{
    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        return $this;
    }
    
    /**
     * Get Javascript contents
     * 
     * @return string
     */
    protected function _getJS()
    {
        return '';
    }
    
    /**
     * Get CSS contents
     * 
     * @return type
     */
    protected function _getCSS()
    {
        return <<<CSS
    .emarsys-span { display:block; float: left; }
    .emarsys-span label { margin-left:20px; display: block; }
    .emarsys-span select { margin-left:20px; display: block; width: 300px; }
    .emarsys-span.buttons { padding-top: 130px; margin-left: 20px; }
    .emarsys-header-span label { font-weight: bold; white-space: nowrap; }
CSS;
    }
    
    /**
     * Get content to display before element
     * 
     * @param type $element
     * 
     * @return string
     */
    protected function _getBeforeElementHtml($element)
    {
        return '';
    }
    
    /**
     * Get content to display after element
     * 
     * @param type $element
     * 
     * @return string
     */
    protected function _getAfterElementHtml($element)
    {
        return '';
    }
    
    /**
     * Adds SPAN element
     * 
     * @param string $id
     * @param string $label
     */
    protected function _addNote($label, $text, $id)
    {
        $el = new Varien_Data_Form_Element_Note(array('label'=> $label));
        $el->setId($id)->setNoSpan(false)->setWrapColumnSpan(false)->setText($text);
        $this->addElement($el);
        return $this;
    }    

    protected function _addHeaderNote($label)
    {
        $el = new Varien_Data_Form_Element_Note(array('label'=> $label));
        $id = md5(rand(10000, 99999) . $label);
        $el->setId($id)->setNoSpan(false)->setWrapHeaderSpan(true)->setText('');
        $this->addElement($el);
        return $this;
    }    

    /**
     * Adds SPAN element
     * 
     * @param string $id
     * @param string $label
     */
    protected function _addSpan($id, $label)
    {
        $el = new Varien_Data_Form_Element_Note(array('label'=> $label));
        $el->setNoSpan(true)->setId($id)->setWrapColumnSpan(true)->setHidden(true);
        $this->addElement($el);
        return $this;
    }
    
    /**
     * Adds custom SELECT element
     * 
     * @param string $id
     * @param string $label
     * @param array  $values
     */
    protected function _addSelector($id, $label, $values=array())
    {
        $el = new Emarsys_Suite2_Block_Adminhtml_Form_Element_Select(array('label'=> $label, 'values' => $values, 'size' => 20));
        $el->setNoSpan(true)->setId($id)->setWrapColumnSpan(true);
        $this->addElement($el);
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';
        if ($css = $this->_getCSS()) {
            $html .= '<style>' . "\n$css\n" . '</style>';
        } 
        
        $html .= $this->_getJS();
        $this->_prepareForm();
        foreach ($this->getElements() as $element) {
            $html .= $this->_getBeforeElementHtml($element);
            if ($element->getHidden()) {
                $style = " style=\"display:none;\"";
            } else {
                $style = "";
            }

            if ($element->getWrapHeaderSpan()) {
                $html .= "<span class=\"emarsys-header-span\"$style>" . $element->toHtml() . '</span>';
            }
            else if ($element->getWrapColumnSpan()) {
                $html .= "<span class=\"emarsys-span\"$style>" . $element->toHtml() . '</span>';
            } else {
                $html .= $element->toHtml();
            }

            $html .= $this->_getAfterElementHtml($element);
        }

        return $html;
    }
}
