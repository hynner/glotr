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
	/** @var \GLOTR\GLOTRApi */
	protected $glotrApi;
	/** @var array */
	protected $parameters;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	/** @var \Nette\Database\Connection */
	protected $connection;
	/** @var \GLOTR\Users */
	protected $users;
	/** @var \GLOTR\Authenticator */
	protected $authenticator;

	public function __construct(\Nette\DI\Container $context, \GLOTR\GLOTRApi $glotrApi, \Nette\Localization\ITranslator $translator, \Nette\Database\Connection $conn, \GLOTR\Users $usr, \GLOTR\Authenticator $auth)
	{
		parent::__construct($context);
		$this->glotrApi = $glotrApi;
		$this->parameters = $context->parameters;
		$this->translator = $translator;
		$this->connection = $conn;
		$this->users = $usr;
		$this->authenticator = $auth;
	}
	protected function startup()
	{
		parent::startup();

		/* for case I need to get all strings to translate, that cannot be analyzed from code directly
		$aI = $this->context->espionages->allInfo;
		$keys = array("planet_buildings", "planet_resources", "planet_defence", "planet_fleet", "researches", "moon_buildings");
		foreach($keys as $k)
			foreach($aI[$k] as $v)
				echo "<br>".__(str_replace("_", " ", str_replace("moon_", "",$v)))."<br>"; */
		// setup permissions
		if($this->getUser()->isLoggedIn())
		{
			$user = $this->getUser();
			if(!isset($this->lang) && array_key_exists($user->identity->lang, $this->parameters["langs"]))
			{
				$this->lang = $user->identity->lang;
			}

		}

		if(!isset($this->lang) || !array_key_exists($this->lang, $this->parameters["langs"]))
		{
			$this->lang = $this->parameters["lang"];

		}
		$this->translator->setLang($this->lang);
		/**
		 * @TODO: wait for nette 2.1 to be stable and use factory
		 */
		$nav = new Navigation\Navigation($this, "nav_info");
		$homepage = $nav->setupHomepage(__("Information"), "");
		$homepage->add(__("Overview"), $this->link("Homepage:"));
		$homepage->add(__("Search in database"), $this->link("Information:search"), "perm_search");
		$homepage->add(__("View systems"), $this->link("Information:systems"), "perm_galaxyview");
		$homepage->add(__("Score history"), $this->link("Information:scoreHistory"));
		$homepage->add(__("Score inactivity"), $this->link("Information:scoreInactivity"));
		$homepage->add(__("Fleet movements"), $this->link("Information:fleetMovements"), "perm_fleet_movements");
		$nav->setTranslator($this->translator);
		$nav->setCurrentByUrl();

		$nav = new Navigation\Navigation($this, "nav_admin");
		$homepage = $nav->setupHomepage(__("Settings"), $this->link("User:"));
		$homepage->add(__("Your settings"), $this->link("User:userSettings"));
		$homepage->add(__("User management"), $this->link("Admin:users"), "perm_user_mng");
		$homepage->add(__("Permissions"), $this->link("Admin:permissions"), "perm_perm_mng");
		if($this->parameters["enableSync"] === TRUE)
			$homepage->add(__("Synchronization"), $this->link("Admin:syncSetup"), "perm_sync_mng");
		$nav->setTranslator($this->translator);
		$nav->setCurrentByUrl();

		// setup permissions
		try{
			$this->addPermission("perm_user_mng", __("Manage users"));
			$this->addPermission("perm_perm_mng", __("Manage permissions"));
			$this->addPermission("perm_diplomacy", __("Diplomacy"));
			$this->addPermission("perm_update", __("Update"));
			$this->addPermission("perm_search", __("Search"));
			$this->addPermission("perm_galaxyview", __("System browsing"));
			$this->addPermission("perm_detail", __("Player/alli detail"));
			$this->addPermission("perm_activity", __("Player activity"));
			$this->addPermission("perm_planet_info", __("Espionages"));
			$this->addPermission("perm_fleet_movements", __("Fleet movements"));
			$this->addPermission("perm_sync_mng", __("Manage synchronization"));
		}
		catch(PDOException $e)
		{
			// database is not ready yet
		}
		// setup timezone
		if($this->getUser()->isLoggedIn())
			$timezone = $this->getUser()->getIdentity()->timezone;
		if(isset($timezone))
			date_default_timezone_set($timezone);
		else
		{
			try{
				date_default_timezone_set($this->glotrApi->getServerData("timezone"));
			}
			catch(\PDOException $e)
			{
				// database is not ready yet
			}

		}
		//now try to setup mysql timezone
		try
		{
			$offset = date("Z");
			$zone = ($offset < 0) ? "-" : "+";
			$h = floor($offset / 3600);
			$offset -= $h*3600;
			$zone .= $h;
			$zone .= ":";
			$m = floor($offset / 60);
			if($m < 10)
				$m = "0".$m;
			$zone .= $m;

			$this->connection->query("SET time_zone = ?;", $zone);
		}
		catch(\PDOException $e)
		{
			echo $e->getMessage();
		}

		$translator = $this->translator;
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
		$template->registerHelperLoader("\GLOTR\Helpers::loader");
		$template->setTranslator($this->translator);
		$template->parameters = $this->parameters;
		return $template;

	}
	public function flashMessage($message, $type = "info", $noTranslate = false)
	{
		if($this->isAjax())
			$this->invalidateControl("flashMessages");

		if($this->translator && $noTranslate === FALSE)
		{
			$message = $this->translator->translate($message);
		}
		parent::flashMessage($message, $type);
	}

	protected function handlePermissions($perm_needed)
	{
		if(!$this->glotrApi->checkPermissions($perm_needed))
		{
			$this->flashMessage("You don´t have permission for this action!", "error");
			$this->redirect("Homepage:");
		}
	}
	protected function addPermission($name, $label = NULL)
	{
		if(is_null($label))
			$label = $name;
        $this->users->addPermissionColumn($name);
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
		return new GLOTR\LangSelectionForm($this->parameters["langs"], $this->lang);
	}
	/**
	 * @return \GLOTR\Authenticator
	 */
	public function getAuthenticator()
	{
		return $this->authenticator;
	}
}
