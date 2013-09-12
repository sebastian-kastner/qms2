var layerId = 'attFormContainer';

function showLayer()
{
    var container = $("#" + layerId);
    
    $('body').append('<div id="overlay"></div>');

    $('#overlay').css({
        width       :   '100%',
        height      :   $(document).height(),
        position    :   'absolute',
        left        :   0,
        top     :   0,
        backgroundColor :   '#FFFFFF',
        zIndex      :   9990,
        opacity     :   0
    }).fadeTo(200, 0.7);

    /* container.css({
        zIndex        :   9999
    }); */
    
    container.fadeIn();
    return false;
}

function hideLayer()
{
    $("#" + layerId).fadeOut();
    $("#overlay").remove();
    return false;
}