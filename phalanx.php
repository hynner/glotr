<?php
$url = "fleet_movements";
$method = "POST";
$arr = array(
	"timestamp" => time(),
	// from where was this movement observed, same format as origin/destination
	"observation" => array(
		"coords" => array(
			"galaxy" => 1,
			"system" => 21,
			"position" => 1
		),
		"moon" => false
	),
	// also send empty movements!
	"movements" => array(
		1226385 => array(
			"id_parent" => 1226384, // filled only for ACS attack and even starting fleet has to have it filled, NULL or skipped for other missions
			"mission" => "acs_attack", //'attack','transport','deployment','acs_attack','acs_defend','expedition','harvest','colonization','espionage','moon_destruction'
			"arrival" => time() + 100*3600, // if returning is false then this is arrival at destination, otherwise it´s arrival at origin
			"returning" => false,
			"origin" => array(
				// id_planet => 123456, // currently it can´t be obtained from ogame page, but the options is there
				"coords" => array(
					"galaxy" => 1,
					"system" => 24,
					"position" => 10
				),
				"moon" => false // movement can also be taken from event list
			),
			"destination" => array(
				// id_planet => 123456, // currently it can´t be obtained from ogame page, but the options is there
				"coords" => array(
					"galaxy" => 1,
					"system" => 21,
					"position" => 1
				),
				"moon" => false
			),
			// from where was this movement observed, same format as origin/destination
			// and only include it when it´s different than the main one! (eg. event list)
			/*"observation" => array(
				"coords" => array(
					"galaxy" => 1,
					"system" => 21,
					"position" => 1
				),
				"moon" => false
			),*/
			// as always send also ships that doesn´t fly (except solar_satellite !!!)
			"fleet" => array(
				"large_cargo" => 0,
				"heavy_fighter" => 0,
				"recycler" => 0,
				"espionage_probe" => 0,
				"small_cargo" => 0,
				"colony_ship" => 0,
				"destroyer" => 0,
				"cruiser" => 0,
				"battleship" => 0,
				"battlecruiser" => 0,
				"bomber" => 0,
				"deathstar" => 209,
				"light_fighter" => 0
			),
			// only filled when you know them - metal, crystal, deuterium
			"resources" => array(),
		),
	)

);