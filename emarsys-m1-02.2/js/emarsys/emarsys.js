/* Emarsys JS*/

function changeValue(url,emarsyseventId, magentoeventId, Id)
{
    Element.hide('loading_mask_loader');

    var url = url + '?emarsyseventId=' + emarsyseventId + '&magentoeventId=' + magentoeventId  + '&Id=' + Id;
    new Ajax.Request(
        url, {
        method: 'get',
        asynchronous: false
        }
    );
}

function placeholderRedirect(url)
{
    window.location = url;
}

function changePlaceholderValue(url,id,event_mapping_id,new_emarsys_placeholder)
{
    var url = url + '?event_mapping_id=' + event_mapping_id + '&emarsys_placeholder_name=' + new_emarsys_placeholder + '&id=' + id;

    new Ajax.Request(
        url, {
        method: 'get',
        asynchronous: false
        }
    );
}

function openMyPopup(url) 
{

    var url = url;

    if ($('browser_window') && typeof(Windows) != 'undefined') {
        Windows.focus('browser_window');
        return;
    }

    var dialogWindow = Dialog.info(
        null, {
        closable:true,
        resizable:false,
        draggable:true,
        className:'magento',
        windowClassName:'popup-window',
        title:'JSON Request',
        top:150,
        width:800,
        height:420,
        zIndex:1000,
        recenterAuto:false,
        hideEffect:Element.hide,
        showEffect:Element.show,
        id:'browser_window',
        url:url,
        onClose:function (param, el) {
            //alert('onClose');
        }
        }
    );
}

function closePopup() 
{
Windows.close('browser_window');
}
/*
Validation for emarsys place holders only Alphanumerics and underscores are allowed
*/
function placeHolderValidation(redirect) 
{
   var placeHolder = document.getElementsByClassName("emarsys-placeholder");
    var errorMessages = document.getElementsByClassName("placeholder-error");
    var totalErrorCount = 0;
        for(var i=0; i<placeHolder.length; i++) {
            emarsys_placeholder = placeHolder[i].value;
            var regexp = /^[a-zA-Z0-9-_]+$/;
         if (emarsys_placeholder.search(regexp) == -1)
           {
            totalErrorCount = totalErrorCount+1;
            errorMessages[i].show();
            }else{
                errorMessages[i].hide();
                 }
            }

          if (totalErrorCount == 0)
           {
             window.location.href = redirect;
           }

}

function mappingConfirmation(message,url)
{

    if(confirm(message)) {
        document.getElementById('loading-mask').style.display = 'block';
        setLocation(url);
    }
    
}
