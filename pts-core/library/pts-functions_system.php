<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_system.php: Include system functions.

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

function pts_hw_string($return_string = true)
{
	return phodevi::system_hardware($return_string);
}
function pts_sw_string($return_string = true)
{
	// TODO: port to Phodevi module similar to the pts_hw_string()
	// Returns string of software information
	$sw = array();

	$sw["OS"] = phodevi::read_property("system", "operating-system");
	$sw["Kernel"] = phodevi::read_property("system", "kernel") . " (" . phodevi::read_property("system", "kernel-architecture") . ")";
	$sw["Desktop"] = phodevi::read_property("system", "desktop-environment");
	$sw["Display Server"] = phodevi::read_property("system", "display-server");
	$sw["Display Driver"] = phodevi::read_property("system", "display-driver-string");
	$sw["OpenGL"] = phodevi::read_property("system", "opengl-driver");
	$sw["Compiler"] = phodevi::read_property("system", "compiler");
	$sw["File-System"] = phodevi::read_property("system", "filesystem");
	$sw["Screen Resolution"] = phodevi::read_property("gpu", "screen-resolution-string");

	$sw = pts_remove_unsupported_entries($sw);

	return pts_process_string_array($return_string, $sw);
}
function pts_available_sensors()
{
	return array(
	new phodevi_sensor("temp", "gpu", array("gpu", "temperature"), "Celsius"),
	new phodevi_sensor("temp", "cpu", array("cpu", "temperature"), "Celsius"),
	new phodevi_sensor("temp", "hdd", array("disk", "temperature"), "Celsius"),
	new phodevi_sensor("temp", "sys", array("system", "temperature"), "Celsius", "System"),
	new phodevi_sensor("battery", "power", array("system", "power-consumption"), "Milliwatts"),
	new phodevi_sensor("battery", "current", array("system", "power-current"), "microAmps"),
	new phodevi_sensor("voltage", "cpu", array("system", "cpu-voltage"), "Volts"),
	new phodevi_sensor("voltage", "v3", array("system", "v3-voltage"), "Volts", "+3.33V"),
	new phodevi_sensor("voltage", "v5", array("system", "v5-voltage"), "Volts", "+5.00V"),
	new phodevi_sensor("voltage", "v12", array("system", "v12-voltage"), "Volts", "+12.00V"),
	new phodevi_sensor("freq", "cpu", array("cpu", "current-frequency"), "Megahertz"),
	new phodevi_sensor("freq", "gpu", array("gpu", "current-frequency"), "Megahertz"),
	new phodevi_sensor("usage", "cpu", array("cpu", "usage"), "Percent"),
	new phodevi_sensor("usage", "gpu", array("gpu", "core-usage"), "Percent"),
	new phodevi_sensor("memory", "system", array("memory", "physical-usage"), "Megabytes"),
	new phodevi_sensor("memory", "swap", array("memory", "swap-usage"), "Megabytes"),
	new phodevi_sensor("memory", "total", array("memory", "total-usage"), "Megabytes"),
	new phodevi_sensor("fan-speed", "gpu", array("gpu", "fan-speed"), "Percent"),
	new phodevi_sensor("fan-speed", "cpu", array("cpu", "fan-speed"), "RPM"),
	new phodevi_sensor("fan-speed", "sys", array("system", "fan-speed"), "RPM", "System"),
	new phodevi_sensor("system", "iowait", array("system", "iowait"), "Percent"),
	new phodevi_sensor("disk-speed", "read", array("disk", "read-speed"), "MB/s"),
	new phodevi_sensor("disk-speed", "write", array("disk", "write-speed"), "MB/s")
	);
}

function pts_sys_sensors_string($return_string = true)
{
	$sensors = array();

	foreach(pts_supported_sensors() as $s)
	{
		$sensors[$s->get_formatted_hardware_type() . " " . $s->get_sensor_string()] = $s->read_sensor() . " " . $s->get_sensor_unit();
	}

	return pts_process_string_array($return_string, $sensors);
}
function pts_supported_sensors()
{
	static $supported_sensors = null;

	if($supported_sensors == null)
	{
		$supported_sensors = array();

		foreach(pts_available_sensors() as $pts_sensor)
		{
			if($pts_sensor->read_sensor() != -1)
			{
				array_push($supported_sensors, $pts_sensor);
			}
		}
	}

	return $supported_sensors;
}
function pts_remove_unsupported_entries($array)
{
	$clean_elements = array();

	foreach($array as $key => $value)
	{
		if($value != -1 && !empty($value))
		{
			$clean_elements[$key] = $value;
		}
	}

	return $clean_elements;
}
function pts_system_identifier_string()
{
	$components = array(phodevi::read_property("cpu", "model"), phodevi::read_name("motherboard"), phodevi::read_property("system", "operating-system"), phodevi::read_property("system", "compiler"));
	return base64_encode(implode("__", $components));
}
function pts_process_string_array($return_string, $array)
{
	if($return_string)
	{
		$return = "";

		foreach($array as $type => $value)
		{
			if($return != "")
			{
				$return .= ", ";
			}

			$return .= $type . ": " . $value;
		}
	}
	else
	{
		$return = $array;
	}

	return $return;
}

?>
