<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	/** @persistent */
	public $lang;
	protected $permissions;
	protected function startup()
	{
		parent::startup();

		if(!isset($this->lang) || !in_array($this->lang, $this->context->parameters["langs"]))
		{
			$this->lang = $this->context->parameters["lang"];

		}
		$this->context->translator->setLang($this->lang);
		// setup permissions
		if($this->getUser()->isLoggedIn())
		{
			$acl = new Nette\Security\Permission;
			// each user gets his own role, by his username
			$user = $this->getUser();
			$user->authenticatedRole = $user->getIdentity()->username;
			$acl->addRole($user->getIdentity()->username);
			// now create resources
			$acl->addResource("administration");
			//$acl->allow($user->getIdentity()->username, "administration");
			$user->setAuthorizator($acl);

		}
		$nav = new Navigation\Navigation($this, "nav_info");
		$homepage = $nav->setupHomepage("Overview", $this->link("Homepage:"));
		$homepage->add("Search in database", $this->link("Information:search"));
		$nav->setTranslator($this->context->translator);
		$nav->setCurrentByUrl();

		$nav = new Navigation\Navigation($this, "nav_admin");
		$homepage = $nav->setupHomepage("Settings", $this->link("User:"));
		$homepage->add("Your settings", $this->link("User:userSettings"));
		$homepage->add("User management", $this->link("Admin:users"));
		$homepage->add("Permissions", $this->link("Admin:permissions"));
		$nav->setTranslator($this->context->translator);
		$nav->setCurrentByUrl();

		// setup permissions
		$this->addPermission("perm_user_mng", "Manage users");
		$this->addPermission("perm_perm_mng", "Manage permissions");

		// setup timezone
		if($this->getUser()->isLoggedIn())
			$timezone = $this->getUser()->getIdentity()->timezone;
		if(isset($timezone))
			date_default_timezone_set($timezone);
		else
			date_default_timezone_set($this->context->server->timezone);

	}
	public function createTemplate($class= NULL)
	{
		$template = parent::createTemplate($class);


		$template->setTranslator($this->context->translator);

		return $template;

	}
	public function flashMessage($message, $type = "info")
	{
		if($this->isAjax())
			$this->invalidateControl("flashMessages");
		if($this->context->hasService("translator"))
		{
			$message = $this->context->translator->translate($message);
		}
		parent::flashMessage($message, $type);
	}
	protected function checkPermissions($perm_needed)
	{
		if(!is_array($perm_needed))
			$perm_needed = array($perm_needed);
		$cond = true;
		foreach($perm_needed as $key => $val)
		{
			if(is_integer($key))
			{
				$property = $val;
				$value = 1; // default value
			}
			else
			{
				$property = $key;
				$value = $val;
			}
			$cond = $cond && isset($this->getUser()->getIdentity()->$property) && ((is_array($value)) ? in_array($this->getUser()->getIdentity()->$property, $value) : $this->getUser()->getIdentity()->$property == $value);

		}
		return $cond;

	}
	protected function handlePermissions($perm_needed)
	{
		if(!$this->checkPermissions($perm_needed))
		{
			$this->flashMessage("You don´t have permission for this action!", "error");
			$this->redirect("Homepage:");
		}
	}
	protected function addPermission($name, $label = NULL)
	{
		if(!is_null($label))
			$label = $this->context->translator->translate($label);
		else
			$label = $name;

		$this->permissions[$name] = $label;
	}
	protected function setUserParams($params)
	{
		$user = $this->getUser()->getIdentity();
		foreach($params as $key => $value)
				$user->$key = $value;
	}
}
