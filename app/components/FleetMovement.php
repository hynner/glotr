<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class FleetMovement extends GLOTRControl
{
	/** @var array */
	protected $keys;
	protected $data;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}
	public function injectFleetKeys($keys)
	{
		if ($this->keys) {
            throw new Nette\InvalidStateException('Fleet keys have already been set');
        }
        $this->keys = $keys;
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
		$template->setTranslator($this->translator);
		$template->fleetKeys = $this->keys;
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
		$control->injectTranslator($this->translator);
		$control->injectFleetKeys($this->keys);
		return $control;
	}

}