<?php
class NokiaValidateXml
{
    public $relativePath;
    public $xmlFile;


    function __construct($relativePath, $xmlFile)
    {
        $this->relativePath = $relativePath;
        $this->xmlFile = $xmlFile;
    }

    function validate()
    {
        $doc = new \DOMDocument();
        if (@$doc->load($this->relativePath . $this->xmlFile)) {
            return true;
        } else {
            return false;
        }
    }

    function cleanXMLStringCustom($xml_string, $find, $replace_with)
    {
        try {
            $cleaned_xml_string = str_replace($find, $replace_with, $xml_string);
            return $cleaned_xml_string;
        } catch (Exception $e) {
            return 'exception';
        }
    }
}
