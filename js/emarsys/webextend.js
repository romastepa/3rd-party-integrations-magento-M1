/* Emarsys Webextend JS*/

function changeAttributeValue(url,emarsysAttributeCodeId, magentoAttributeCode, magentoAttributeCodeLabel, Id)
{
    Element.hide('loading_mask_loader');

    var url = url + '?emarsysAttributeCodeId=' + emarsysAttributeCodeId + '&magentoAttributeCode=' + magentoAttributeCode + '&magentoAttributeCodeLabel=' + magentoAttributeCodeLabel + '&Id=' + Id;
    new Ajax.Request(
        url, {
            method: 'get',
            asynchronous: false
        }
    );
}

function updateRecord(url,storeId,recordid,attribute_code,attribute_label)
{
    url = url + '?recordid=' + recordid + '&storeId=' + storeId + '&attribute_code=' + attribute_code + '&attribute_label=' + attribute_label;
    document.location.href=url;
}

function deleteRecord(url,recordid,storeId)
{
    url = url + '?recordid=' + recordid + '&storeId=' + storeId;
    document.location.href=url;
}

function validateForm()
{
    var fieldName = document.getElementsByName("field_name[]");
    var fieldLabel = document.getElementsByName("field_label[]");
    for(var x=0;x<fieldName.length;x++)
    {
        if((fieldName[x].value==null || fieldName[x].value=="") || (fieldLabel[x].value==null||fieldLabel[x].value==""))
        {
            fieldName[x].style.border = "1px solid red";
            fieldLabel[x].style.border = "1px solid red";
        }
    }
    for(var x=0;x<fieldName.length;x++)
    {
        if((fieldName[x].value==null || fieldName[x].value=="") || (fieldLabel[x].value==null||fieldLabel[x].value==""))
        {
            fieldName[x].style.border = "1px solid red";
            fieldLabel[x].style.border = "1px solid red";
            alert("Please enter Field Name & Field Label");
            return false;
        }
        else {
            fieldName[x].style.border = "";
            fieldLabel[x].style.border = "";
        }
    }
}

function removeElement(elementId) {
    // Removes an element from the document
    var element = document.getElementById(elementId);
    element.parentNode.removeChild(element);
}

function addElement(parentId, elementTag, elementId, html) {
    // Adds an element to the document
    var p = document.getElementById(parentId);
    var newElement = document.createElement(elementTag);
    newElement.setAttribute('id', elementId);
    newElement.innerHTML = html;
    p.appendChild(newElement);
}

var fileId = 0; // used by the addFile() function to keep track of IDs
function addFile() {
    fileId++; // increment fileId to get a unique ID for the new element
    var html = '<input type="text" name="field_name[]" value="" class="fieldName">';
    html += '<input type="text" name="field_label[]" value="" class="fieldLabel">';
    //html += '<select name="attribute_type[]"><option value="varchar">Varchar</option><option value="text">Text</option><option value="character">Character</option><option value="integer">Integer</option><option value="String">String</option><option value="URL">URL</option><option value="StringValue">StringValue</option><option value="Boolean">Boolean</option><option value="Float">Float</option></select>';
    html += '<button type="button" id="button" value="button" onclick="javascript:removeElement(\'file-' + fileId + '\'); return false;">Remove</button>    <div style="clear: both;"></div>';
    addElement('files', 'span', 'file-' + fileId, html);
}

function mappingConfirmation(message,url)
{
    if(confirm(message)) {
        document.getElementById('loading-mask').style.display = 'block';
        setLocation(url);
    }
}