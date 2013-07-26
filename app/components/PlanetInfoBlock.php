<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class PlanetInfoBlock extends GLOTRControl
{
	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}

	public function render($data, $name, $updated)
	{
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlanetInfoBlock.latte');
		$template->setTranslator($this->translator);
		$template->dateTimeFormat = "j. n. Y H:i:s";
		$template->data = $data;
		$template->name = $name;
		$template->updated = $updated;
		$template->render();
	}

}