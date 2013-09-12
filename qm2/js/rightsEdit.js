var checkedBoxes;
$(document).ready(function() 
{
   $("#rightsEdit input[type=checkbox]").click(checkChildren);
   //alle gewählten checkboxen auswählen und alle children davon selektieren und deaktivieren
   /*checkedBoxes = $("#rightsEdit input[type=checkbox]:checked");
   for(var i=0;i < checkedBoxes.length; i++)
   {
       var name = checkedBoxes[i].name;
       parentChecked(checkedBoxes[i].name, checkedBoxes[i].value);
   }*/
});

/**
 * Wird ausgeführt, wenn eine checkbox geklickt wurde
 * @return void
 */
function checkChildren()
{
    var children = getChildren($(this).attr('name'), $(this).attr('value'));
    //checkbox selektiert => alle children selektieren und deaktivieren
    if($(this).attr('checked'))
    {
        parentChecked($(this).attr('name'), $(this).attr('value'));
    }
    //checkbox deselektiert => alle children deselektieren und aktivieren
    else
    {
        parentUnchecked($(this).attr('name'), $(this).attr('value'));
    }
    
}

/**
 * Aufruf, wenn ein Elternelement selektiert wurde
 * Selektiert und deaktiviert alle Unterelemente
 * @param string name "name" Attribut des Elternelements
 * @param string value "value" Attribut des Elternelements
 * @return void
 */
function parentChecked(name, value)
{
    var children = getChildren(name, value);
    children.attr('checked', true);
    //children.attr('disabled', true);
}


/**
 * Aufruf, wenn ein Elternelement deselektiert wurde
 * Deselektiert und aktiviert alle Unterelemente 
 * @param string name "name" Attribut des Elternelements
 * @param string value "value" Attribut des Elternelements
 * @return void
 */
function parentUnchecked(name, value)
{
    var children = getChildren(name, value);
    children.attr('checked', false);
    //children.attr('disabled', false);
}

/**
 * Ermittelt alle Unterlemente zu einem Elternelement und gibt diese zurück
 * @param string name "name" Attribut des Elternelements
 * @param boolean value "value" Attribut des Elternelements
 */
function getChildren(name, value)
{
    name = name.substring(0, (name.length-2)) + "_";
    return $("#rightsEdit input[name^='" + name +"']input[value='" + value + "']");
}