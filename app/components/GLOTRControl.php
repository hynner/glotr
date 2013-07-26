<?php

namespace GLOTR\Components;
use Nette\Application\UI\Control;
/**
 * Base class for all of GLOTR´s components, register helpers
 */
class GLOTRControl extends Control
{
	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader("\GLOTR\Helpers::loader");
		return $template;
	}
}