<?php
$validateTranslator = array(
    //NotEmpty (Ist zu überprüfender Wert leer?)
    Zend_Validate_NotEmpty::IS_EMPTY => 'Dieses Feld muss ausgefüllt werden!',
    Zend_Validate_NotEmpty::INVALID => "Dieses Feld muss ausgefüllt werden!",
    
    //Alnum  (Besteht zu überprüfender Wert nur aus Zahlen und Buchstaben?)
    Zend_Validate_Alnum::NOT_ALNUM => '"%value%" besteht nicht nur aus Buchstaben und Zahlen!',
    Zend_Validate_Alnum::INVALID => 'Ungültige Eingabe! Erlaubt sind Zahlen und Buchstaben!',
    Zend_Validate_Alnum::STRING_EMPTY => 'Dieses Feld muss ausgefüllt werden',
    
    //EmailAdress (Ist der zu überprüfende Wert eine gültige eMail Adresse?)
    Zend_Validate_EmailAddress::INVALID => 'Ungültige eMail Adresse angegeben!',
    Zend_Validate_EmailAddress::INVALID_FORMAT => 'Die von Ihnen angegebene eMail Adresse besitzt kein gültiges Format!'
  );