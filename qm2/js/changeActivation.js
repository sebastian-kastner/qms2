var url = '';
var loaderImg = 'img/loader.gif';
var activeImg = 'img/active.gif';
var deactiveImg = 'img/deactive.gif';
var id_prefix = 'active_img_';
var baseUrl;

function changeActivation()
{
    var clickedSrc = $(this).attr('src');
    var newImg;
    var clickedState;
    
    var fullId = $(this).attr('id');
    var id = fullId.substr(id_prefix.length);
    
    if(clickedSrc.substr((clickedSrc.length - activeImg.length), activeImg.length) == activeImg)
    {
        clickedState = 'active';
        baseUrl = clickedSrc.substr(0, clickedSrc.length - activeImg.length);
        newImg = deactiveImg;
    }
    else if(clickedSrc.substr((clickedSrc.length - deactiveImg.length), deactiveImg.length) == deactiveImg)
    {
        clickedState = 'deactive';
        baseUrl = clickedSrc.substr(0, clickedSrc.length - deactiveImg.length); 
        newImg = activeImg;
    }
    else
    {
        return false;
    }
    $(this).attr('src', baseUrl + loaderImg);

    $.post("admin/async/roleActivation", { id: id },
               function(result){
                    //bei erfolg => behandeln des erfolgs
                    if(result == 1)
                    {
                        handleActivationSuccess(fullId, clickedState);
                    }
                    else
                    {
                        handleActivationError(fullId, clickedState);
                    }
               }, "json");
    
    return false;
}

function handleActivationSuccess(fullId, clickedState)
{
    var newImg;
    var newTitle;
    
    if(clickedState == 'active') 
    {
        newImg = deactiveImg;
        newTitle = 'Zum Aktivieren klicken';
    }
    else
    {        
        newImg = activeImg;
        newTitle = 'Zum Deaktivieren klicken';
    }
    
    $('#' + fullId).attr('src', baseUrl + newImg);
    $('#' + fullId).attr('title', newTitle);
}

function handleActivationError(fullId, clickedState)
{
    var newImg;
    var action;
    if(clickedState == 'active') 
    {
        newImg = activeImg;
        action = 'Aktivieren';
    }
    else
    {
        newImg = deactiveImg;
        action = 'Deaktivieren';
    }
    
    $('#' + fullId).attr('src', baseUrl + newImg);
    alert('Fehler beim ' + action + ' des Datensatzes!');
}