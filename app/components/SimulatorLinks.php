<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class SimulatorLinks extends Control
{

	protected $context;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function render($result, $resources, $fleet, $defence, $buildings, $researches)
	{


		$server = $this->context->server->data;
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/SimulatorLinks.latte');
		$template->setTranslator($this->context->translator);
		$user = $this->getPresenter()->getUser()->getIdentity();
		/**
		 * OSIMULATE parameters
		 * for attacker it is the same except d is replaced by a and of course there is no defence
		 * changing the defender/attacker number doesn´t work
		 */
		$def = array(
				"ship_d0_0_b" => "small_cargo",
				"ship_d0_1_b" => "large_cargo",
				"ship_d0_2_b" => "light_fighter",
				"ship_d0_3_b" => "heavy_fighter",
				"ship_d0_4_b" => "cruiser",
				"ship_d0_5_b" => "battle_ship",
				"ship_d0_6_b" => "colony_ship",
				"ship_d0_7_b" => "recyclator",
				"ship_d0_8_b" => "espionage_probe",
				"ship_d0_9_b" => "bomber",
				"ship_d0_10_b" => "solar_satellite",
				"ship_d0_11_b" => "destroyer",
				"ship_d0_12_b" => "death_star",
				"ship_d0_13_b" => "battle_cruiser",
				"ship_d0_14_b" => "rocket_launcher",
				"ship_d0_15_b" => "light_laser",
				"ship_d0_16_b" => "heavy_laser",
				"ship_d0_17_b" => "gauss_cannon",
				"ship_d0_18_b" => "ion_cannon",
				"ship_d0_19_b" => "plasma_turret",
				"ship_d0_20_b" => "small_shield_dome",
				"ship_d0_21_b" => "large_shield_dome"
			);
		if($user->id_player)
			$player = $this->context->players->search($user->id_player);
		$template->osimulate = "http://www.osimulate.com?ref=glotr&lang=".$this->getPresenter()->lang."&uni_speed=".$server["speed"]."&defense_debris=".((int) ($server["defToTF"]*$server["debrisFactor"]))."&defense_repair=".((int) ($server["repairFactor"]*100));
		$template->osimulate .= "&fleet_debris=".((int) ($server["debrisFactor"]*100))."&enemy_pos=".$result['galaxy'].":".$result['system'].":".$result['position'];
		if(!empty($researches))
			$template->osimulate .= "&tech_d0_0=".((int) $researches["weapons_technology"])."&tech_d0_1=".((int) $researches["shielding_technology"])."&tech_d0_2=".((int) $researches["armour_technology"]);
		if(isset($player))
		{
			$template->osimulate .= "&tech_a0_0=".((int) $player["player"]["weapons_technology"])."&tech_a0_1=".((int) $player["player"]["shielding_technology"])."&tech_a0_2=".((int) $player["player"]["armour_technology"]);
			$template->osimulate .= "&engine0_0=".((int) $player["player"]["combustion_drive"])."&engine0_1=".((int) $player["player"]["impulse_drive"])."&engine0_2=".((int) $player["player"]["hyperspace_drive"]);

		}
		$template->osimulate .= "&enemy_name=".$result["name"]."&enemy_metal=".$resources["metal"]."&enemy_crystal=".$resources["crystal"]."&enemy_deut=".$resources["deuterium"];
		$f_empty = !empty($fleet);
		$d_empty = !empty($defence);
		foreach($def as $os_key => $gl_key)
		{
			if($f_empty && isset($fleet[$gl_key]))
				$template->osimulate .= "&$os_key=".$fleet[$gl_key];
			elseif($d_empty && isset($defence[$gl_key]))
				$template->osimulate .= "&$os_key=".$defence[$gl_key];
		}
		$template->render();
	}

}