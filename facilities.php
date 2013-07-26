<?php
$id = 33627870;
$url = "universe/$id";
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
		"robotics_factory" => 10,
		"shipyard" => 10,
		"research_lab" => 9,
		"alliance_depot" => 0,
		"missile_silo" => 0,
		"nanite_factory" => 5,
		"terraformer" => 10

	),
	// properties from other pages, you have to send them too - in order to keep the update format unified
	"fleet" => array(),
	"defence" => array()
	// don´t send researches because they are part of player update!

);