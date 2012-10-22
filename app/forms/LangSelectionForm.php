<?php
namespace GLOTR;
use Nette\Application\UI\Form;

class LangSelectionForm extends MyForm
{
	public function __construct($langs, $lang, IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

		$this->setMethod("GET");
		$this->addSelect("lang", __("Language"), $langs)
				->setDefaultValue($lang);

		$this->addSubmit("set_lang", "OK");

	}

}

