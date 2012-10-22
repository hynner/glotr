<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class PlayerInfoRow extends Control
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
	public function render($result, $odd = false)
	{



		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlayerInfoRow.latte');
		$template->setTranslator($this->context->translator);
		$template->odd = $odd;

		$template->result = $result;

		$template->render();
	}
	protected function createComponentPlanetInfo()
	{
		$control =  new PlanetInfo;
		$control->setContext($this->context);
		$control->setUserPlayer($this->userPlayer);
		return $control;
	}

}