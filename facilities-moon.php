<?php
$id = 33628827; // for now ogame has id_moon_ogame in meta tag ogame-planet-id when you´re on the moon
$url = "universe/$id/moon";
$method = "PATCH";
$arr = array(
	"timestamp" => 1372417002,
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
		"robotics_factory" => 4,
		"shipyard" => 0,
		"lunar_base" => 4,
		"sensor_phalanx" => 2,
		"jump_gate" => 0

	),
	// properties from other pages, you have to send them too - in order to keep the update format unified
	"fleet" => array(),
	"defence" => array()
	// don´t send researches because they are part of player update!

);