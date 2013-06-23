<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;

class ScoreHistoryCharts extends GLOTRControl
{
	/** @var bool */
	protected $redraw;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	/** @var array */
	protected $parameters;
	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}
	public function injectParameters($parameters)
	{
		if ($this->parameters) {
            throw new Nette\InvalidStateException('Parameters have already been set');
        }
        $this->parameters = $parameters;
	}
	public function render($scoreHistory,$selected,  $redraw = false)
	{
		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/ScoreHistoryCharts.latte');
		$template->setTranslator($this->translator);
		$template->redraw = $redraw;
		$template->scoreHistory = $scoreHistory;
		$template->selected = $selected;
		$template->periodDuration = $this->parameters["scoreHistoryPeriod"];
		$template->types = array("score_0" => $this->translator->translate("Total score"), "score_1" => $this->translator->translate("Economy"), "score_2" => $this->translator->translate("Research"), "score_3" => $this->translator->translate("Military") );

		$template->render();
	}


}