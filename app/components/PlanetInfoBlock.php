<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class PlanetInfoBlock extends GLOTRControl
{

	protected $context;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function render($data, $name, $updated)
	{



		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlanetInfoBlock.latte');
		$template->setTranslator($this->context->translator);
		$template->dateTimeFormat = "j. n. Y H:i:s";
		$template->data = $data;
		$template->name = $name;
		$template->updated = $updated;
		$template->render();
	}

}