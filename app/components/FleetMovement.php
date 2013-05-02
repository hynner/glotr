<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class FleetMovement extends GLOTRControl
{

	protected $context;
	protected $data;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function setData($data)
	{
		$this->data = $data;
	}
	public function render($data, $id, $odd = false, $child = false)
	{

		$this->link("");

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/FleetMovement.latte');
		$template->setTranslator($this->context->translator);
		$template->fleetKeys = $this->context->fleetMovements->getFleetKeys();
		$template->movement = $data;
		$template->id = $id;
		$template->child = $child;
		$template->odd = $odd;
		$template->render();
	}
	public function setRedraw($r)
	{
		$this->redraw = $r;
	}
	protected function createComponentFleetMovement()
	{
		$control = new FleetMovement;
		$control->setContext($this->context);
		return $control;
	}

}