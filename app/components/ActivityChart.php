<?php
namespace GLOTR;
use Nette\Application\UI\Control;

class ActivityChart extends Control
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
		$template = $template->setFile(__DIR__ .'/ActivityChart.latte');
		$template->setTranslator($this->context->translator);
		$template->results = $results;
		$template->redraw = $this->redraw;

		$template->render();
	}
	public function setRedraw($r)
	{
		$this->redraw = $r;
	}

}