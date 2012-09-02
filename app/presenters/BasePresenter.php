<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	/** @persistent */
	public $lang;

	protected function startup()
	{
		parent::startup();
		
		if(!isset($this->lang) || !in_array($this->lang, $this->context->parameters["langs"]))
		{
			$this->lang = $this->context->parameters["lang"];

		}
		$this->context->translator->setLang($this->lang);

		$nav = new Navigation\Navigation($this, "nav_info");
		$homepage = $nav->setupHomepage("Overview", $this->link("Homepage:"));
		$homepage->add("Search in database", $this->link("Information:search"));
		$nav->setTranslator($this->context->translator);
		$nav->setCurrentByUrl();
	}
	public function createTemplate($class= NULL)
	{
		$template = parent::createTemplate($class);


		$template->setTranslator($this->context->translator);

		return $template;

	}
	public function flashMessage($message, $type = "info")
	{
		if($this->context->hasService("translator"))
		{
			$message = $this->context->translator->translate($message);
		}
		parent::flashMessage($message, $type);
	}
}
