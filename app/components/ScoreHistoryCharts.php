<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class ScoreHistoryCharts extends GLOTRControl
{

	protected $context;
	protected $redraw;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function render($scoreHistory,$selected,  $redraw = false)
	{



		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/ScoreHistoryCharts.latte');
		$template->setTranslator($this->context->translator);
		$template->redraw = $redraw;
		$template->scoreHistory = $scoreHistory;
		$template->selected = $selected;
		$template->periodDuration = $this->context->parameters["scoreHistoryPeriod"];
		$template->types = array("score_0" => $this->context->translator->translate("Total score"), "score_1" => $this->context->translator->translate("Economy"), "score_2" => $this->context->translator->translate("Research"), "score_3" => $this->context->translator->translate("Military") );

		$template->render();
	}


}