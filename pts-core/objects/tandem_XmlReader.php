<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	tandem_XmlReader.php: The XML reading object for the Phoronix Test Suite.

	Additional Notes: A very simple XML parser with a few extras... Does not currently support attributes on tags, etc.
	A work in progress. This was originally designed for just some select needs in the past. No XML validation is done with this parser, etc.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class tandem_XmlReader
{
	var $XML_DATA = "";
	var $XML_FILE_TIME = null;
	var $XML_FILE_NAME = null;

	var $XML_CACHE_FILE = false; // Cache the entire XML file being parsed
	var $XML_CACHE_TAGS = true; // Cache the tags that are being called

	var $NO_TAG_FALLBACK_VALUE = null;

	static $cache = null;

	function __construct($XML, $DO_CACHE = true)
	{
		if(is_file($XML))
		{
			if(!$DO_CACHE)
			{
				$this->XML_CACHE_FILE = false;
				$this->XML_CACHE_TAGS = false;
			}

			// If you're going to be banging XML files hard through the course of the script, you'll want to flush the PHP file cache
			// clearstatcache();

			$this->XML_FILE_TIME = filemtime($XML);
			$this->XML_FILE_NAME = $XML;

			if($this->XML_CACHE_FILE == true && isset(self::$cache["FILE"][$this->XML_FILE_NAME][$this->XML_FILE_TIME]))
			{
				$this->XML_DATA = self::$cache["FILE"][$this->XML_FILE_NAME][$this->XML_FILE_TIME];
			}

			if(empty($this->XML_DATA))
			{
				$this->XML_DATA = file_get_contents($XML);

				if($this->XML_CACHE_FILE == true)
				{
					self::$cache["FILE"][$this->XML_FILE_NAME][$this->XML_FILE_TIME] = $this->XML_DATA;
				}
			}
		}
		else
		{
			$this->XML_CACHE_FILE = false;
			$this->XML_CACHE_TAGS = false;
			$this->XML_DATA = $XML;
		}
	}
	function getStatement($STATEMENT_NAME)
	{
		return $this->listStatements(true, $STATEMENT_NAME);
	}
	function listStatements($SEARCH_DO = false, $SEARCH_QUERY = "")
	{
		if(!function_exists("preg_match_all"))
		{
			return array(); // TODO: Write a software fallback for this parsing similar to parseXMLString()
		}

		preg_match_all("'<!--(.*?) -->'si", $this->XML_DATA, $statement_matches);
		$return_array = array();

		foreach($statement_matches[0] as $statement)
		{
			$name = substr($statement, 0, strpos($statement, ':'));
			$name = trim(strstr($name, ' '));

			if($SEARCH_DO)
			{
				if($name == $SEARCH_QUERY)
				{
					$value = strstr($statement, ':');
					$value = trim(substr($value, 1, strpos($value, "-->") - 1));

					array_push($return_array, $value);
				}
			}
			else
			{
				array_push($return_array, $name);
			}
		}
		return $return_array;
	}
	function getXMLValue($XML_TAG)
	{
		return $this->getValue($XML_TAG);
	}
	function isDefined($XML_TAG)
	{
		return $this->getValue($XML_TAG) != null;
	}
	function getValue($FULL_XML_TAG, $XML_TAG = null, $XML_MATCH = null, $DO_CACHE = true, $FALLBACK_PROCESS = false)
	{
		if($XML_TAG == null)
		{
			$XML_TAG = $FULL_XML_TAG;
		}
		if($XML_MATCH == null)
		{
			$XML_MATCH = $this->XML_DATA;
		}

		if($this->XML_CACHE_TAGS == true && $DO_CACHE && isset(self::$cache["TAGS"][$this->XML_FILE_NAME][$this->XML_FILE_TIME][$XML_TAG]))
		{
			$XML_MATCH = self::$cache["TAGS"][$this->XML_FILE_NAME][$this->XML_FILE_TIME][$XML_TAG];
		}
		else
		{
			foreach(explode("/", $XML_TAG) as $xml_step)
			{
				$XML_MATCH = $this->parseXMLString($xml_step, $XML_MATCH, false);

				if($XML_MATCH == false)
				{
					if(!$FALLBACK_PROCESS)
					{
						$XML_MATCH = $this->handleXmlZeroTagFallback($FULL_XML_TAG);
					}
					else
					{
						$XML_MATCH = $this->NO_TAG_FALLBACK_VALUE;
					}
				}
			}

			if($this->XML_CACHE_TAGS == true && $DO_CACHE)
			{
				self::$cache["TAGS"][$this->XML_FILE_NAME][$this->XML_FILE_TIME][$XML_TAG] = $XML_MATCH;
			}
		}

		return $XML_MATCH;
	}
	function parseXMLString($TAG, $TO_PARSE, $DO_MULTIPLE = true)
	{
		$return = false;

		if(function_exists("preg_match") && function_exists("preg_match_all"))
		{
			if($DO_MULTIPLE)
			{
				preg_match_all("'<" . $TAG . ">(.*?)</" . $TAG . ">'si", $TO_PARSE, $matches);

				if(count($matches) >= 2)
				{
					$return = $matches[1];
				}
			}
			else
			{
				preg_match("'<" . $TAG . ">(.*?)</" . $TAG . ">'si", $TO_PARSE, $match);

				if(count($match) >= 2)
				{
					$return = $match[1];
				}
			}
		}
		else
		{
			$open_tag = "<" . $TAG . ">";
			$close_tag  = "</" . $TAG . ">";

			if($DO_MULTIPLE)
			{
				$temp_r = array();
				$temp = $TO_PARSE;

				do
				{
					$return = null;

					if(($start = strpos($temp, $open_tag)) !== false)
					{
						$temp = substr($temp, $start + strlen($open_tag));

						if(($end = strpos($temp, $close_tag)) !== false)
						{
							$return = substr($temp, 0, $end);
							$temp = substr($temp, strlen($return) + strlen($close_tag));
						}
					}

					if($return != null)
					{
						array_push($temp_r, $return);
					}
				}
				while($return != null);

				if(count($temp_r) > 0)
				{
					$return = $temp_r;
				}
			}
			else
			{
				if(($start = strpos($TO_PARSE, $open_tag)) !== false)
				{
					$temp = substr($TO_PARSE, $start + strlen($open_tag));

					if(($end = strpos($temp, $close_tag)) !== false)
					{
						$temp = substr($temp, 0, $end);
						$return = $temp;
					}
				}
			}
		}

		return $return;
	}
	function handleXmlZeroTagFallback($XML_TAG)
	{
		return $this->NO_TAG_FALLBACK_VALUE;
	}
	function getXMLValues($XML_TAG)
	{
		return $this->getXMLArrayValues($XML_TAG);
	}
	function getXMLArrayValues($XML_TAG)
	{
		return $this->getArrayValues($XML_TAG, $this->XML_DATA);
	}
	function getArrayValues($XML_TAG, $XML_MATCH)
	{
		$xml_steps = explode("/", $XML_TAG);
		$this_xml = $XML_MATCH;

		for($i = 0; $i < count($xml_steps) - 2; $i++)
		{
			$this_xml = $this->getValue($XML_TAG, $xml_steps[$i], $this_xml, false);
		}

		$next_xml_step = $xml_steps[count($xml_steps) - 2];
		$xml_matches = $this->parseXMLString($next_xml_step, $this_xml);

		$return_array = array();
		$extraction_tags = explode(',', end($xml_steps));
		$extraction_tags_count = count($extraction_tags);

		for($i = 0; $i < count($xml_matches); $i++)
		{
			if($extraction_tags_count == 1)
			{
				$this_item = $this->getValue($XML_TAG, $extraction_tags[0], $xml_matches[$i], false);
				array_push($return_array, $this_item);
			}
			else
			{
				if($i == 0)
				{
					foreach($extraction_tags as $extract)
					{
						$return_array[$extract] = array();
					}
				}
				foreach($extraction_tags as $extract)
				{
					$this_item = $this->getValue($XML_TAG, $extract, $xml_matches[$i], false);
					array_push($return_array[$extract], $this_item);
				}
			}
		}
		return $return_array;
	}
	function setFileCaching($BOOL)
	{
		$this->XML_CACHE_FILE = ($BOOL == true);
	}
	function setTagCaching($BOOL)
	{
		$this->XML_CACHE_TAGS = ($BOOL == true);
	}
	function setCaching($BOOL)
	{
		$this->setFileCaching($BOOL);
		$this->setTagCaching($BOOL);
	}
}

?>
