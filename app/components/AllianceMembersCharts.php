<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;
/**
 * Alliance members score charts
 */
class AllianceMembersCharts extends GLOTRControl
{
	/** @var array */
	protected $redraw;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}
	public function render($results)
	{

		$this->link("");

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/AllianceMembersCharts.latte');
		$template->setTranslator($this->translator);

		$template->results = $results;
		$template->types = array("score_0" => $this->translator->translate("Total score"), "score_1" => $this->translator->translate("Economy"), "score_2" => $this->translator->translate("Research"), "score_3" => $this->translator->translate("Military") );

		$template->render();
	}
	public function setRedraw($r)
	{
		$this->redraw = $r;
	}

}