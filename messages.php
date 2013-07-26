<?php
$url = "messages";
$method = "POST";
$arr = array(
	"timestamp" => time(), // used only for authentication
	"espionage_reports" => array(
		// id_message => data
		1263238 => array(
			"timestamp" => time(),
			"activity" => FALSE, // timestamp -  activity from espionage report - counts as galaxyview
			// for now this is the only way to identify a planet directly from ogame page, because there is no id
			"coordinates" => array(
				"galaxy" => 1,
				"system" => 1,
				"position" => 6,
				"moon" => FALSE
			),
			"playername" => "weteha", // for now only coords + playername is used for identification, if it doesn´t match report is skipped
			"scan_depth" => "research", // 'resources','fleet','defence','building','research'
			// the following key => value arrays, with traditional GLOTR keys = english name, lowercased, spaces replaced by _, - replaced by empty string
			// send also keys with value 0, don´t send what you don´t know!
			"resources" => array(
				"metal" => 10000,
				"crystal" => 10000,
				"deuterium" => 1875,
				"energy" => 0
			),
			"fleet" => array(
				"solar_satellite" => 0,
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
				"deathstar" => 0,
				"light_fighter" => 0
			),
			"defence" => array(
				"antiballistic_missiles" => 0,
				"interplanetary_missiles" => 0,
				"ion_cannon" => 0,
				"large_shield_dome" => 0,
				"small_shield_dome" => 0,
				"light_laser" => 0,
				"heavy_laser" => 0,
				"gauss_cannon" => 0,
				"plasma_turret" => 0,
				"rocket_launcher" => 0
			),
			// don´t send moon buildings for planet and vice-versa
			"building" => array(
				"metal_mine" => 0,
				"crystal_mine" => 0,
				"deuterium_synthesizer" => 0,
				"solar_plant" => 0,
				"fusion_reactor" => 0,
				"metal_storage" => 0,
				"crystal_storage" => 0,
				"missile_silo" => 0,
				"alliance_depot" => 0,
				"deuterium_tank" => 0,
				"robotics_factory" => 0,
				"shipyard" => 0,
				"research_lab" => 0,
				"terraformer" => 0,
				"nanite_factory" => 0,
				"shielded_metal_den" => 0,
				"underground_crystal_den" => 12,
				"seabed_deuterium_den" => 0
			),
			"research" => array(
				"intergalactic_research_network" => 0,
				"impulse_drive" => 0,
				"combustion_drive" => 0,
				"hyperspace_drive" => 0,
				"armour_technology" => 0,
				"shielding_technology" => 0,
				"weapons_technology" => 0,
				"graviton_technology" => 0,
				"astrophysics" => 0,
				"computer_technology" => 0,
				"espionage_technology" => 8,
				"plasma_technology" => 0,
				"hyperspace_technology" => 0,
				"ion_technology" => 0,
				"laser_technology" => 0,
				"energy_technology" => 0
			),
		),

	),
	 // only activities are interesting, this is for both circs and PMs
	"messages" => array(
		// id message
		1294512 => array(
			//"id_player" => 123456, - only send if it´s known, there is currently no way how to determine it from ogame page directly
			"playername" => "hynner1",
			"timestamp" => time() // timestamp of
		),
	),
	// enemy scans
	"scans" => array(
		// id message
		1294512 => array(
			//"id_player" => 123456, - only send if it´s known, there is currently no way how to determine it from ogame page directly
			"playername" => "hynner1",
			"timestamp" => time() // timestamp of
		),
	),



);