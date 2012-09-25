<?php
namespace GLOTR;
use Nette\Application\UI\Form;

class MyForm extends Form
{
	public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
		\Nette\Forms\Container::extensionMethod('addDatePicker', function (\Nette\Forms\Container $container, $name, $label = NULL) {
            return $container[$name] = new \JanTvrdik\Components\DatePicker($label);
        });
	}
	public function addError($message)
	{

		if($this->getTranslator())
			$message = $this->getTranslator()->translate($message);

		parent::addError($message);
	}
	
}

