<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class SimulatorLinks extends GLOTRControl
{
	/** @var array */
	protected $userPlayer;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	/** @var array */
	protected $serverData;
	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}

	public function injectUserPlayer($player)
	{
		if ($this->userPlayer) {
            throw new Nette\InvalidStateException('User player has already been set');
        }
		$this->userPlayer = $player;
	}
	public function injectServerData($data)
	{
		if ($this->serverData) {
            throw new Nette\InvalidStateException('Server data has already been set');
        }
		$this->serverData = $data;
	}
	public function render($result, $resources, $fleet, $defence, $buildings, $researches, $prefix)
	{
		$server = $this->serverData;
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/SimulatorLinks.latte');
		$template->setTranslator($this->translator);
		$osimulate = new Simulators\OSimulate($this->userPlayer);
		$osimulate->setLang($this->getPresenter()->lang);
		$template->osimulate = $osimulate->makeLink($result, $resources, $fleet, $defence, $buildings, $researches, $prefix, $server);

		$template->render();
	}

}