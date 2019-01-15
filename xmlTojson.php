<?php

$xml='<company>
    <name>Outlandish Ideas</name>
    <link href="http://outlandishideas.co.uk">Website</link>
    <person>Abi</person>
    <person>Tamlyn</person>
    <address street="yes">
    	<door>15</door>
        <street>Longford Street</street>
        <city>London</city>
    </address>
</company>';

/*------------------------------------------------------------------------------------
								convert_XML_str_TO_JSON_array	
------------------------------------------------------------------------------------*/
function convert_XML_str_TO_JSON_array(&$JSON_arr_ptr, $index, $xml_values )
{
	$index = $index + 1;

	if ($index >= sizeof($xml_values))
		return;

	// OpenTagStack variable keeps the parent of open tags to return it when we see close tag
	static $OpenTagStack = array();
	//every element of array(converted xml array) contains tag,type and level,in addition to these some of the contains value(text) and attribute
	// extract attribute
	$tag = $xml_values[$index]['tag'];
	$type = $xml_values[$index]['type'];
	$level = $xml_values[$index]['level'];
	//check if element contains value
	$value = NULL;
	if(array_key_exists('value',$xml_values[$index]))
		$value = $xml_values[$index]['value'];
	//check if element contains attributes
	$attributes = NULL;
	if(array_key_exists('attributes',$xml_values[$index]))
		$attributes = $xml_values[$index]['attributes'];

	// create a temporary node contains values and attributes of the xml_values to be added into JSON array
	$txt_attr_arr = array();
	//check if value is set
	if(isset($value))
		$txt_attr_arr['_text'] = $value;
	//check if attributes is set
	if(isset($attributes))
		foreach($attributes as $attr => $val)
			$txt_attr_arr["_$attr"] = $val;


	// checking type
	switch ($type)
	{
		//when type is open
		case 'open':

			// keep the return pointer (i.e., parent) in the static variable to be retrieved by the close tag based on the level value
			$OpenTagStack[$level-1] = &$JSON_arr_ptr;
			// if tag name does not exist,put the $txt_attr_arr in JSON_arr_ptr,and call the next element
			if (!in_array($tag, array_keys($JSON_arr_ptr)))
			{
				$JSON_arr_ptr[$tag] = $txt_attr_arr;
				convert_XML_str_TO_JSON_array(
								$JSON_arr_ptr[$tag], 
								$index, 
								$xml_values
							);
			}
			// if tag name already exists,add the txt_attr_arr to the JSON_arr_ptr and call the last element of the tag name
			else
			{
				array_push($JSON_arr_ptr[$tag], $txt_attr_arr);				
				convert_XML_str_TO_JSON_array(
							$JSON_arr_ptr[$tag][sizeof($JSON_arr_ptr[$tag]) - 1],
							$index, 
							$xml_values
						);
			}				
			break;
		// when type is close
		case 'close':
			//decrement the current level and call the element
			$parent = &$OpenTagStack[$level-1];
			
			convert_XML_str_TO_JSON_array(
						$parent, 
						$index, 
						$xml_values
					);
			break;
		//when type is complete
		case 'complete':
			// if tag name does not exist in JSON_arr_ptr,add attributes and values if they are set
			if(!isset($JSON_arr_ptr[$tag]))
			{
				if(isset($attributes))
					$JSON_arr_ptr[$tag] = $txt_attr_arr;
				else
					$JSON_arr_ptr[$tag] = $value;
			}
			// if tag name exist
			else
			{
				//if tag name has an element and it is array,push the value to the array
				if (
						isset($JSON_arr_ptr[$tag][0]) 
						and
						is_array($JSON_arr_ptr[$tag][0]))
				{
					array_push($JSON_arr_ptr[$tag],$value);
				}
				//put attributes and value in JSON_arr_ptr with current tag name if they are set
				else
				{
					if(isset($attributes))
						$JSON_arr_ptr[$tag] = array($JSON_arr_ptr[$tag],$txt_attr_arr);
					else 
						$JSON_arr_ptr[$tag] = array($JSON_arr_ptr[$tag],$value);	
				}
			}

			convert_XML_str_TO_JSON_array(
						$JSON_arr_ptr, 
						$index, 
						$xml_values
					);
			break;
		//when type is cdata
		case 'cdata':

			if (!in_array('_text', array_keys($JSON_arr_ptr)))
				$JSON_arr_ptr['_text'] = $value;
			else
				$JSON_arr_ptr['_text'] = $JSON_arr_ptr['_text'].$value;

			convert_XML_str_TO_JSON_array(
						$JSON_arr_ptr, 
						$index, 
						$xml_values
					);
			break;			
	}

}
/*------------------------------------------------------------------------------------
						convert_JSON_array_TO_String	
------------------------------------------------------------------------------------*/
function convert_JSON_array_TO_String(&$JSON_obj,&$JSON_str)
{
	$JSON_str = $JSON_str."{";
	$cnt = 0;
	foreach ($JSON_obj as $key => $value)
	{
		$cnt = $cnt + 1;
		
		if ($cnt >= 2)
				$JSON_str = $JSON_str.","	;

		if (is_array($value))
		{
			$JSON_str = $JSON_str."\"".$key."\":";
			convert_JSON_array_TO_String($value,$JSON_str);
		}
		else
		{
			$JSON_str = $JSON_str."\"".$key."\":"."\"".$value."\"";
		}
	}
	$JSON_str = $JSON_str."}";
}

/*------------------------------------------------------------------------------------
										MAIN	
------------------------------------------------------------------------------------*/
$parser = xml_parser_create();

xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
//convert xml to array
$re = xml_parse_into_struct($parser, $xml, $xml_values, $xml_index);// this function return 0 when xml in invalid

xml_parser_free($parser);

if ($re === 1)
{
	$JSON_arr = array();
	$JSON_str = '';

	convert_XML_str_TO_JSON_array($JSON_arr, -1, $xml_values);

	convert_JSON_array_TO_String($JSON_arr,$JSON_str);

	//$JSON_str= preg_replace( "/\//", "\/", $JSON_str );
	//print json array
	// print_r($JSON_arr);
	//print json string
	print_r($JSON_str);

}
else
{
	echo "invalid XML";
}


?>