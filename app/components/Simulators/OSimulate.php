<?php
namespace GLOTR\Components\Simulators;
/**
 * OSimulate
 */
class OSimulate extends BaseSimulator
{
	const BASE_URL = "http://www.osimulate.com";
	/** @var array */
	protected $langs = array(
		"en" => "en",
		"cz" => "cz"
	);
	protected $propertyMap = array(
		// fleet and defence
		"small_cargo" => "ship_d0_0_b",
		"large_cargo" => "ship_d0_1_b",
		"light_fighter" => "ship_d0_2_b",
		"heavy_fighter" => "ship_d0_3_b",
		"cruiser" => "ship_d0_4_b",
		"battleship" => "ship_d0_5_b",
		"colony_ship" => "ship_d0_6_b",
		"recycler" => "ship_d0_7_b",
		"espionage_probe" => "ship_d0_8_b",
		"bomber" => "ship_d0_9_b",
		"solar_satellite" => "ship_d0_10_b",
		"destroyer" => "ship_d0_11_b",
		"deathstar" => "ship_d0_12_b",
		"battlecruiser" => "ship_d0_13_b",
		"rocket_launcher" => "ship_d0_14_b",
		"light_laser" => "ship_d0_15_b",
		"heavy_laser" => "ship_d0_16_b",
		"gauss_cannon" => "ship_d0_17_b",
		"ion_cannon" => "ship_d0_18_b",
		"plasma_turret" => "ship_d0_19_b",
		"small_shield_dome" => "ship_d0_20_b",
		"large_shield_dome" => "ship_d0_21_b",
		"antiballistic_missiles" => "abm_b",
		// server properties
		"debrisFactor" => "fleet_debris",
		"speed" => "uni_speed",
		"defToTF" => "defense_debris", // this will contain number of % of defence to df
		"repairFactor" => "defence_repair",
		"rapidFire" => "",
		// general properties
		"destination" => "enemy_pos",
		"plunder" => "plunder_perc",
		"enemy_name" => "enemy_name",
		// resources
		"metal" => "enemy_metal",
		"crystal" => "enemy_crystal",
		"deuterium" => "enemy_deut",
		// engines
		"combustion_drive" => "engine0_0",
		"impulse_drive" => "engine0_1",
		"hyperspace_drive" => "engine0_2",
		// techs attacker
		"a_weapons" => "tech_a0_0",
		"a_shields" => "tech_a0_1",
		"a_armour" => "tech_a0_2",
		// techs defender
		"d_weapons" => "tech_d0_0",
		"d_shields" => "tech_d0_1",
		"d_armour" => "tech_d0_2",


	);
	
}