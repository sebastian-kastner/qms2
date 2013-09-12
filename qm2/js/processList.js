var plusImg = 'img/plus.gif';
var minusImg = 'img/minus.gif';
var blankImg = 'img/listSpacer.gif';
var loaderImg = 'img/loader.gif';
var processCache = new Array();

function processListClick()
{
    var process_id = $(this).attr('id');

    if($(this).attr('src') == plusImg)
    {
        setLoaderImg(process_id);
        showSubProcesses(process_id);
    }
    else if ($(this).attr('src') == minusImg)
    {
        setPlusImg(process_id);
        hideSubProcesses(process_id);
    }
    
}

function showSubProcesses(process_id)
{
    if(processCache[process_id] == null)
    {
        $.post("process/async/getProcessChilds", { process_id: process_id },
                   function(processChilds){
                         processCache[process_id] = processChilds;
                        appendProcesses(processChilds, process_id);
                     
                   }, "json");
    }
    else
    {
        appendProcesses(processCache[process_id], process_id);
    }
    
}

function appendProcesses(processChilds, process_id)
{    
    var list = $('<ul style="margin-left:10px;" class="processList" id="parent_id_' + process_id + '"></ul>');
    
    $.each(processChilds, function(i, process){
        
        var node = $('<li id="process_id_' + process.process_id + '"></li>');
        
        var num_childs = parseInt(process.num_childs);
        if(num_childs > 0)
        {
            var img = $('<img src="' + plusImg + '" alt="Unterprozesse anzeigen" title="Unterprozesse anzeigen" id="' + process.process_id + '" />');
        }
        else
        {
            var img = $('<img src="' + blankImg + '" alt="" />');
        }
        node.append(img);
        node.append('&nbsp;');
        
        var href = "process/" + process.process_id;
        
        $('<span class="process_notation"><a href="' + href + '">' + process.notation + '</a></span>').appendTo(node);
        node.append('&nbsp;&nbsp;');
        $('<a href="' + href + '">' + process.name + '</a>').appendTo(node);
        
        
        list.append(node);
    });
    
    
    $("#process_id_" + process_id).append(list.fadeIn());
    $("#parent_id_" + process_id + " img").click(processListClick);
    setMinusImg(process_id);
    
    //fenster um die höhe des angehängten inhalts nach unten scrollen 
    //scrollBy(0, list.height());
}

function hideSubProcesses(process_id)
{
    //$("#parent_id_" + process_id).fadeOut(500, function() { this.remove(); });
    $("#parent_id_" + process_id).remove();
}

function setMinusImg(process_id)
{
    $("#" + process_id).attr('src', minusImg);
}

function setPlusImg(process_id)
{
    $("#" + process_id).attr('src', plusImg);
}

function setLoaderImg(process_id)
{
    $("#" + process_id).attr('src', loaderImg);
}
