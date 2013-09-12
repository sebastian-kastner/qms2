
var textfieldId = 'attValue';
var headId = 'formContainerHead';
var isActiveId = 'attIsActive';
var phaId;
var currentAttribute;

/**
 * Attributwert in das Formularfeld schreiben
 * @param int process_id
 * @param int attribute_id
 * @return void
 */
function writeAttributeValue(process_id, attribute_id)
{
    $.post(
        'process/async/getProcessAttribute',                    //uri für request
        { attribute_id: attribute_id, process_id: process_id },    //parameter für request
        function(attribute)                                     //callback funktion
        {
          currentAttribute = attribute_id;
          phaId = attribute.pha_id;
          $('#' + textfieldId).val(attribute.attribute_value);
          $('#' + headId).html($('#processName').html() + " - " + attribute.name);
        },
        'json'                                                    //ergebnis als json dekodieren
          );

}

/**
 * Speichert den Attributwert in der Datenbank (bzw schickt den Request an das PHP Script)
 * @param int process_id
 * @return void
 */
function saveAttribute(process_id)
{    
    var value = $('#' + textfieldId).val();
    var isActive = $('#' + isActiveId).val();
    
    $('#attribute_div_' + currentAttribute).html(value);
    
    $.post(
        'process/async/saveProcessAttribute',
        {pha_id : phaId,
         process_attribute_id : currentAttribute, 
         process_id: process_id, 
         attribute_value : value, 
         is_active : isActive}
    );
}