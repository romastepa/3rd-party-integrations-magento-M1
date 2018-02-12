<?php
class Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Transmail extends Emarsys_Suite2_Block_Adminhtml_System_Config_Form_Abstract 
  implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_suiteEvents = null;
    
    protected function _getJS()
    {
        $url = Mage::helper('adminhtml')->getUrl('*/suite2/eventInfo');
        return <<<JS
<script type="text/javascript">
var suiteEvents = {$this->_getSuiteEvents()};
var registeredEvents = [];
var target = $('emarsys_suite2_transmail_registry_events');
var registry = [];
$(document).observe('dom:loaded', reloadEvents);

$(document).observe('dom:loaded', function() {
    handleSwitch();
    $('emarsys_suite2_transmail_settings_enabled').observe('change', handleSwitch);
    $('registered_as').setStyle({marginLeft: '20px', marginTop: '5px'});
    $('registered_events').observe('change', function() {
        var target = $('registered_as').up('.emarsys-span');
        target.hide();
        if (this.value) {
            new Ajax.Request('$url?isAjax=true&eventId=' + this.value, {
                onSuccess: function(response) {
                    if (response.responseJSON) {
                        target.show();
                        $('registered_as').update(response.responseJSON.join('<br/>'));
                    }
                }
            });
        }
    });
});
        
function handleSwitch()
{
    var source = $('emarsys_suite2_transmail_settings_enabled');
    var target = $('emarsys_suite2_transmail_registry').up('.section-config');

    if (source.value == 1) {
        target.show();
    } else {
        target.hide();
    }
    return true;
}

function reloadEvents()
{
    $('suite_events').update();
    $('registered_events').update();

    if (target.value) {
        registry = JSON.parse(target.value);
        if (!registry) {
            registry = [];
        }
    }

    for (evtId in suiteEvents) {
        if (typeof(suiteEvents[evtId]) != 'function') {
            if (registry.indexOf(evtId) >= 0) {
                $('registered_events').insert(new Element('option', {value:evtId}).update(evtId + ' - ' + suiteEvents[evtId]));
            } else {
                $('suite_events').insert(new Element('option', {value:evtId}).update(suiteEvents[evtId]));
            }
        }
    }
}

function eventRegister()
{
    if ((id = $('suite_events').value) && (registry.indexOf(id) == -1)) {
        registry.push(id);
        target.value = JSON.stringify(registry);
        reloadEvents();
    }
}

function eventUnregister()
{
    if (id = $('registered_events').value) {
        registry = registry.without(id);
        target.value = JSON.stringify(registry);
        reloadEvents();
    }
}
</script>
JS;
    }
    
    public function _getCSS()
    {
        return parent::_getCSS() . "\n#row_emarsys_suite2_transmail_registry_events {display:none}\n";
    }
    
    protected function _getSuiteEvents()
    {
        if (is_null($this->_suiteEvents)) {
            try {
                $this->_suiteEvents = Mage::helper('core')->jsonEncode(Mage::getSingleton('emarsys_suite2/api_event')->getEvents());
            } catch (Exception $e) {
                $this->_suiteEvents = array();
                Mage::getSingleton('adminhtml/session')->addError('API error encountered: ' . $e->getMessage());
            }
        }

        return $this->_suiteEvents;
    }
    
    protected function _prepareForm()
    {
        $this->_addSelector('suite_events', 'Available Suite Events');
        $this->_addSelector('registered_events', 'Registered Suite Events');
        $this->_addSpan('registered_as', 'Currently registered as');
    }
    
    protected function _getAfterElementHtml($element)
    {
        $result = '';
        if ($element->getId() == 'suite_events') {
            $result .= '<span class="emarsys-span buttons"><button type="button" onclick="eventRegister()"> > </button><br/><br/><button type="button" onclick="eventUnregister()"> < </button>' . '</span>';
        }

        return $result;
    }
    
}