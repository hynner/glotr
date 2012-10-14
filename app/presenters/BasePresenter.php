<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	/** @persistent */
	public $lang;
	protected $permissions;
	protected $player_statuses;
	protected $alliance_statuses;
	protected function startup()
	{
		parent::startup();

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
			if(!isset($this->lang) && array_key_exists($user->identity->lang, $this->context->parameters["langs"]))
			{
				$this->lang = $user->identity->lang;
			}

		}

		if(!isset($this->lang) || !array_key_exists($this->lang, $this->context->parameters["langs"]))
		{
			$this->lang = $this->context->parameters["lang"];

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
		$this->addPermission("perm_diplomacy", "Diplomacy");
		// setup timezone
		if($this->getUser()->isLoggedIn())
			$timezone = $this->getUser()->getIdentity()->timezone;
		if(isset($timezone))
			date_default_timezone_set($timezone);
		else
		{
			try{
				date_default_timezone_set($this->context->server->timezone);
			}
			catch(\PDOException $e)
			{
				// database is not ready yet
			}

		}
		$translator = $this->context->translator;
		// I write it in this way, so that translations could be statically analyzed
		$this->player_statuses = array(
			"war" => $translator->translate("war"),
			"farm" => $translator->translate("farm"),
			"target" => $translator->translate("target"),
			"observe" => $translator->translate("observe"),
			"friends" => $translator->translate("friends"),
			"trader" => $translator->translate("trader"),
			"confederation" => $translator->translate("confederation"),
			"wing" => $translator->translate("wing")
		);
		$this->alliance_statuses = array(
			"war" => $translator->translate("war"),
			"friends" => $translator->translate("friends"),
			"trader" => $translator->translate("trader"),
			"confederation" => $translator->translate("confederation"),
			"wing" => $translator->translate("wing")
		);


	}
	public function createTemplate($class= NULL)
	{
		$template = parent::createTemplate($class);


		$template->setTranslator($this->context->translator);
		$template->parameters = $this->context->parameters;
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

	protected function handlePermissions($perm_needed)
	{
		if(!$this->context->authenticator->checkPermissions($perm_needed))
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
	protected function createComponentLangSelectionForm()
	{
		return new GLOTR\LangSelectionForm($this->context->parameters["langs"], $this->lang);
	}
}
