<?php

class Emarsys_Suite2_Block_Adminhtml_Form_Element_Select extends Varien_Data_Form_Element_Select
{
    public function getHtmlAttributes()
    {
        return array('size', 'title', 'class', 'style', 'onclick', 'onchange', 'disabled', 'readonly', 'tabindex');
    }

}