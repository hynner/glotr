<?php
$id = 33627870;
$url = "universe/$id";
$method = "PATCH";
$arr = array(
	"timestamp" => 1372417001,
	// always send resources when updating the planet
	"resources" => array(
		"metal" => 441975883,
		"crystal" => 2094762,
		"deuterium" => 2853,
		"energy" => 31665
	),
	"buildings" => array(
		// send only know buildings - eg. don´t send facilities from resources page, but send known buildings with level 0!!
		// individual keys are just english name, lowercased and with _ instead of spaces
		"metal_mine" => 41,
		"crystal_mine" => 33,
		"deuterium_synthesizer" => 22,
		"solar_plant" => 25,
		"fusion_reactor" => 0,
		"metal_storage" => 18,
		"crystal_storage" => 16,
		"deuterium_tank" => 14,
		"shielded_metal_den" => 0,
		"underground_crystal_den" => 0,
		"seabed_deuterium_den" => 0

	),
	"fleet" => array(
		// again as with buidlings send only known ships
		"solar_satellite" => 772
	),
	// properties from other pages, you have to send them too - in order to keep the update format unified
	"defence" => array()
	// don´t send researches because they are part of player update!

);