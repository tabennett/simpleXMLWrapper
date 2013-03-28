<?php
/**
 * SimpleXML Wrapper class file.
 *
 * Wrapper for the SimpleXML extension. Simplifies the output of XML documents.
 * Requires php 5 or greater.
 *
 * @author     	  Travis Bennett
 * @link          http://www.travisbennett.net
 * @Copyright (C) 2012  Travis Bennett
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */	
class SimpleXMLWrapper {

	public $simpleXMLObject;
	
	/**
	 * Constructor function
	 * @return void
	 */
	function __construct($data = "") {
		if(is_array($data)){
			$xmlString = $this->arrayToXML($data);
			$this->simpleXMLObject = new SimpleXMLElement($xmlString);
		}
		elseif(is_string($data)){
			if(strstr($data, "<")){
				$this->loadString($data);
			}
			elseif(file_exists($data)){ 
				$this->loadFile($data);
			}
		}
	}

	/**
	 * @description - Wrapper function for the simplexml_load_file function
	 * @link http://www.php.net/manual/en/function.simplexml-load-file.php
	 * @access public
	 * @return SimpleXMLElement:element
	 * @return void 
	 */
	function loadFile($fileName){
		$this->simpleXMLObject = simplexml_load_file($fileName);
	}

	/**
	 * @description - Wrapper function for the simplexml_load_string function
	 * @link http://www.php.net/manual/en/function.simplexml-load-string.php
	 * @access public
	 * @param string $data
	 * @return void
	 */
	function loadString($data){
		$this->simpleXMLObject = simplexml_load_string($data);
	}


	/**
	 * @description - Wrapper function for SimpleXMLElement::asXML
	 * @link http://www.php.net/manual/en/simplexmlelement.asxml.php
	 * @access public
	 * @param string $fileName
	 * @return boolean
	 */
	function asXML($fileName = ""){
		return $this->simpleXMLObject->asXML($fileName);
	}

	/**
	 * @description - Wrapper function for SimpleXMLElement::attributes
	 * @link http://www.php.net/manual/en/simplexmlelement.attributes.php
	 * @access public
	 * @param string $ns
	 * @param bool $is_prefix
	 * @return SimpleXMLElement, NULL 
	 */
	function attributes($ns = "", $is_prefix = false){
		return $this->simpleXMLObject->attributes($ns, $is_prefix);
	}

	/**
	 * @description - Function to convert an array into an xml string (without XML heading)
	 * @access public
	 * @param array $data
	 * @return string
	 */
	function toXml($data = array()){
		return $this->arrayToXML($data);
	}

	/**
	 * @description - Function to convert the current SimpleXMLElement:element object into an array
	 * @access public
	 * @return array
	 */
	function toArray(){
		return $this->xmlToArray($this->simpleXMLObject);
	}

	/**
	 * @description - Getter function for the current SimpleXMLElement:element object used by this object
	 * @access public
	 * @return SimpleXMLElement 
	 */
	function getSimpleXML(){
		return $this->simpleXMLObject;
	}

	/**
	 * @description - Convert a SimpleXML object into an array
	 * @access protected
	 * @param object $xml
	 * @param integer $count - The number of occurences of a given parent element (used to detect root node and duplicate nodes).
	 * @param array $data 
	 * @return array
	 */
	
	public function xmlToArray($xml, $count = 0, $data = array()) {
		foreach($xml as $key => $value){
			$elementCount = count($xml->{$key});		// get the number of elements with this name
			
			if(count($value->children()) > 0){
				/***child node with children***/
				if($elementCount > 1){
					// element is a duplicate and has children
					$data[$key][] = $this->xmlToArray($value, $elementCount);
				}
				else{
					// element is not a duplicate and has children
					$data = array_merge($data, $this->xmlToArray($value, $elementCount));
				}
			}
			else{
				/***child node with no childre of its own***/
				if($attributes = $value->attributes()){

					// generate the attributes array 
					$array = array();
					foreach ($attributes as $attr => $attrValue) {
						$array[$attr] = (string)$attrValue;
					}

					// handle duplicates
					if($elementCount > 1)
						$data[$key][] = array('value' => (string)$value, '@attributes' => $array);
					else
						$data[$key] = array('value' => (string)$value, '@attributes' => $array);
				}
				else{
					// handle duplicates
					if($elementCount > 1)
						$data[$key][] = (string)$value;
					else
						$data[$key] = (string)$value;
				}
			}
		}
		
		$nodeName = $xml->getName();

		// check for presence of attributes
		if($attributes = $xml->attributes()){
			
			// generate the attributes array
			$array = array();
			foreach ($attributes as $attr => $attrValue) {
				$array[$attr] = (string)$attrValue;
			}
			
			// handle duplicates and root node
			if($count != 0){
				if($count > 1){
					return array('value' => $data, '@attributes' => $array);
				}
				else
					return array($nodeName => array('value' => $data, '@attributes' => $array));		//root node
			}
			else{
				$data = array_merge($data, array('@attributes' => $array));
				return array($nodeName => $data);	//root node
			}
		}
		else{
			return array($nodeName => $data);
		}
	}
	

	/**
	 * @description - Convert an array in an xml string.
	 * @access protected
	 * @param array $xmlArray
	 * @param string $lookAhead - Used for replacing the numeric array keys of duplicate nodes with an associative value
	 * @param string $string
	 * @return string
	 */
	public function arrayToXML($xmlArray, $lookAhead = "", $string = ""){
		if(is_array($xmlArray)){
			foreach($xmlArray as $key => $value){

				if($lookAhead != "")
					$key = $lookAhead;
				
				if(is_array($value)) {

					// check for presence of an attributes array
					if(array_key_exists('@attributes', $value)){

						// generate the attributes list
						$attributesString = "";
						foreach($value['@attributes'] as $attributeName => $attributeValue){
							$attributesString .= "$attributeName='$attributeValue' ";
						}

						// check for values and build tags accordingly
						if(array_key_exists('value', $value)){
							if (is_array($value['value']))
								$string .= "\t<$key $attributesString>" . $this->arrayToXML($value['value']) . "</$key>\n";
							else
								$string .= "\t<$key $attributesString>$value[value]</$key>\n";
						}
						else{
								// trim the @attributes element off of the $value array (should be the last element of the array)	
								unset($value['@attributes']);	
								$string .= "\t<$key $attributesString>" . $this->arrayToXML($value) . "</$key>\n";
						}	
					}
					else{
						// check for values and build tags accordingly
						if(array_key_exists('value', $value)){
							if (is_array($value['value']))
								$string .= "\t<$key>" . $this->arrayToXML($value['value']) . "</$key>\n";
							else
								$string .= "\t<$key>$value[value]</$key>\n";
						}
						else{
							// check for duplicate children nodes and set $lookAhead if need be
							if(is_numeric(array_shift(array_keys($value))))
								$string .= $this->arrayToXML($value, $key);
							else
								$string .= "\t<$key>" . $this->arrayToXML($value) . "</$key>\n";
						}
					}
				}
				else{
					//non-parent node: has a string value but no child arrays
					$string .= "\t<$key>$value</$key>\n";
				}
				
			}
		}
		return $string;
	}
}

