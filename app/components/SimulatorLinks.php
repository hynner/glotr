<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class SimulatorLinks extends Control
{

	protected $context;
	protected $userPlayer;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function setUserPlayer($player)
	{
		$this->userPlayer = $player;
	}
	public function render($result, $resources, $fleet, $defence, $buildings, $researches, $prefix)
	{


		$server = $this->context->server->data;
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/SimulatorLinks.latte');
		$template->setTranslator($this->context->translator);

		/**
		 * OSIMULATE parameters
		 * for attacker it is the same except d is replaced by a and of course there is no defence
		 * changing the defender/attacker number doesn´t work
		 */
		$def = array(
				"ship_d0_0_b" => $prefix."small_cargo",
				"ship_d0_1_b" => $prefix."large_cargo",
				"ship_d0_2_b" => $prefix."light_fighter",
				"ship_d0_3_b" => $prefix."heavy_fighter",
				"ship_d0_4_b" => $prefix."cruiser",
				"ship_d0_5_b" => $prefix."battle_ship",
				"ship_d0_6_b" => $prefix."colony_ship",
				"ship_d0_7_b" => $prefix."recyclator",
				"ship_d0_8_b" => $prefix."espionage_probe",
				"ship_d0_9_b" => $prefix."bomber",
				"ship_d0_10_b" => $prefix."solar_satellite",
				"ship_d0_11_b" => $prefix."destroyer",
				"ship_d0_12_b" => $prefix."death_star",
				"ship_d0_13_b" => $prefix."battle_cruiser",
				"ship_d0_14_b" => $prefix."rocket_launcher",
				"ship_d0_15_b" => $prefix."light_laser",
				"ship_d0_16_b" => $prefix."heavy_laser",
				"ship_d0_17_b" => $prefix."gauss_cannon",
				"ship_d0_18_b" => $prefix."ion_cannon",
				"ship_d0_19_b" => $prefix."plasma_turret",
				"ship_d0_20_b" => $prefix."small_shield_dome",
				"ship_d0_21_b" => $prefix."large_shield_dome"
			);

		$template->osimulate = "http://www.osimulate.com?ref=glotr&lang=".$this->getPresenter()->lang."&uni_speed=".$server["speed"]."&defense_debris=".((int) ($server["defToTF"]*$server["debrisFactor"]))."&defense_repair=".((int) ($server["repairFactor"]*100));
		$template->osimulate .= "&fleet_debris=".((int) ($server["debrisFactor"]*100))."&enemy_pos=".$result['galaxy'].":".$result['system'].":".$result['position'];
		/*
		 * If researches are unknown, $researches is the array with all keys set to NULL
		 */
		$researchesUnknown = array_values(array_unique($researches));
		$researchesUnknown = is_null($researchesUnknown[0]) && (count($researchesUnknown) === 1);
		
		if(!$researchesUnknown)
			$template->osimulate .= "&tech_d0_0=".((int) $researches["weapons_technology"])."&tech_d0_1=".((int) $researches["shielding_technology"])."&tech_d0_2=".((int) $researches["armour_technology"]);

		if(!empty($this->userPlayer))
		{
			if(!$researchesUnknown) // don´t fill in techs if target´s techs are unknown
				$template->osimulate .= "&tech_a0_0=".((int) $this->userPlayer["player"]["weapons_technology"])."&tech_a0_1=".((int) $this->userPlayer["player"]["shielding_technology"])."&tech_a0_2=".((int) $this->userPlayer["player"]["armour_technology"]);
			$template->osimulate .= "&engine0_0=".((int) $this->userPlayer["player"]["combustion_drive"])."&engine0_1=".((int) $this->userPlayer["player"]["impulse_drive"])."&engine0_2=".((int) $this->userPlayer["player"]["hyperspace_drive"]);

		}
		$template->osimulate .= "&enemy_name=".$result[$prefix."name"]."&enemy_metal=".$resources[$prefix."metal"]."&enemy_crystal=".$resources[$prefix."crystal"]."&enemy_deut=".$resources[$prefix."deuterium"];
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