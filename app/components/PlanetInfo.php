<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class PlanetInfo extends Control
{

	protected $context;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function render($result, $moon = false)
	{

		$prefix = "planet_";
		if($moon)
			$prefix = "moon_";

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlanetInfo.latte');
		$template->setTranslator($this->context->translator);
		$template->result = $result;
		$template->dateTimeFormat = "j. n. Y H:i:s";
		$tmp = $this->context->espionages->allInfo;
		$template->resources = $this->context->espionages->filterData($result, $tmp[$prefix."resources"], true);
		$template->fleet = $this->context->espionages->filterData($result, $tmp[$prefix."fleet"], false);
		$template->defence = $this->context->espionages->filterData($result, $tmp[$prefix."defence"], false);
		$template->buildings = $this->context->espionages->filterData($result,$tmp[$prefix."buildings"], false);
		$template->researches = $this->context->espionages->filterData($result, $tmp["researches"], true);
		$template->prefix = $prefix;
		$template->moon = $moon;
		$template->render();
	}
	protected function createComponentPlanetInfoBlock()
	{
		$control =  new PlanetInfoBlock;
		$control->setContext($this->context);
		return $control;
	}
	protected function createComponentSimulatorLinks()
	{
		$control = new SimulatorLinks;
		$control->setContext($this->context);
		return $control;
	}
}