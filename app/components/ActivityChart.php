<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class ActivityChart extends GLOTRControl
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	/** @var boolean */
	protected $redraw;
	/** @var array */
	protected $activityTypes;
	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}
	public function injectActivityTypes($types)
	{
		if ($this->activityTypes) {
            throw new Nette\InvalidStateException('Activity types have already been set');
        }
        $this->activityTypes = $types;
	}
	public function render($results)
	{

		$this->link("");

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/ActivityChart.latte');
		$template->setTranslator($this->translator);
		$template->results = $results;
		$template->redraw = $this->redraw;
		$template->types = $this->activityTypes;

		$template->render();
	}
	public function setRedraw($r)
	{
		$this->redraw = $r;
	}

}