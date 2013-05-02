<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class AllianceMembersCharts extends GLOTRControl
{

	protected $context;
	protected $redraw;
	public function setContext($context)
	{
		$this->context = $context;
	}
	public function render($results)
	{

		$this->link("");

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/AllianceMembersCharts.latte');
		$template->setTranslator($this->context->translator);

		$template->results = $results;
		$template->types = array("score_0" => $this->context->translator->translate("Total score"), "score_1" => $this->context->translator->translate("Economy"), "score_2" => $this->context->translator->translate("Research"), "score_3" => $this->context->translator->translate("Military") );

		$template->render();
	}
	public function setRedraw($r)
	{
		$this->redraw = $r;
	}

}