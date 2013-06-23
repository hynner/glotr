<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class PlayerInfoRow extends GLOTRControl
{
	/** @var \Nette\Callback */
	protected $planetInfoFactory;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}

	public function injectPlanetInfoFactory(\Nette\Callback $f)
	{
		if ($this->planetInfoFactory) {
            throw new Nette\InvalidStateException('PlanetInfo factory has already been set');
        }
        $this->planetInfoFactory = $f;
	}
	public function render($result, $odd = false)
	{
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlayerInfoRow.latte');
		$template->setTranslator($this->translator);
		$template->odd = $odd;

		$template->result = $result;

		$template->render();
	}
	protected function createComponentPlanetInfo()
	{
		return $this->planetInfoFactory->invoke();
	}

}