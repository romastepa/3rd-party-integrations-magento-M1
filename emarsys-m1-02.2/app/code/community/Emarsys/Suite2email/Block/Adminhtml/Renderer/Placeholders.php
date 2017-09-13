<?php

/**
 *
 * @category   Suite2email
 * @package    Emarsys_Suite2email
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 * Added extra div for validation and displaying it if validation fails
 */
class Emarsys_Suite2email_Block_Adminhtml_Renderer_Placeholders extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Rendering text area in the placeholders screen
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        try {
            $url = $this->getUrl('*/*/changeValue');
            ?>
            <textarea class="emarsys-placeholder" rows="2" cols="100"
                      onchange="changePlaceholderValue('<?php printf($url); ?>','<?php printf($row->getData('id')); ?>','<?php printf($row->getData('event_mapping_id')); ?>',this.value)"><?php printf(trim($row->getEmarsysPlaceholderName())); ?></textarea>
            <div class="placeholder-error validation-advice" style="display:none;">Placeholders can only have
                Alphanumerics
                and Underscores.
            </div>
        <?php
        } catch (Exception $e) {
            Mage::helper('emarsys_suite2')->log($e->getMessage(), $this);
        }
    }
}

