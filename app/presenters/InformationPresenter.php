<?php

namespace FrontModule;
use \Nette\Application\UI\Form,
	\Nette\Security as NS,
	\GLOTR;

class InformationPresenter extends BasePresenter
{
	protected $id_player;
	protected $id_alliance;
	protected $userPlayerData;
	protected function startup()
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{

			$this->redirect("Sign:in");
		}

	}
	public function actionSearch()
	{
		$this->handlePermissions("perm_search");
		$sess = $this->getSession("searchForm");

		if(isset($sess->searchFormValues))
		{

			$this->loadSearchResults($sess->searchFormValues);

		}
		else
		{
			$this->template->results = array();
		}

		$this->template->dateTimeFormat = "j. n. Y H:i:s";
	}
	public function actionPlayerInfo($id)
	{
		$this->handlePermissions("perm_detail");
		if(!$id)
			throw new Nette\Application\BadRequestException;
		$this->id_player = $id;
		$keys = array("player", "alliance", "fs", "score_inactivity", "planets");
		if($this->glotrApi->checkPermissions('perm_activity'))
		{
			$keys[] = "activity";
			$keys[] = "activity_all";
			$this->template->show_activity = true;
		}
		else
			$this->template->show_activity = false;

		$this->template->results = $this->glotrApi->getPlayerDetail($id, $this->getUserPlayer(), $keys);
		$this->template->scores = array(
			0 => "Total",
			1 => "Economy",
			2 => "Research",
			3 => "Military",
			5 => "Military Built",
			6 => "Military Destroyed",
			4 => "Military Lost",
			7 => "Honor"
		);

		$this->template->statuses = $this->player_statuses;
		$this->template->perm_diplomacy =  $this->glotrApi->checkPermissions("perm_diplomacy");
	}
	public function actionAllianceInfo($id)
	{

		$this->handlePermissions("perm_detail");
		if(!$id)
			throw new Nette\Application\BadRequestException;
		$this->template->results = $this->glotrApi->getAllianceDetail($id, $this->getUserPlayer(), array("all"));
		$this->template->scores = array(
			0 => "Total",
			1 => "Economy",
			2 => "Research",
			3 => "Military",
			5 => "Military Built",
			6 => "Military Destroyed",
			4 => "Military Lost",
			7 => "Honor"
		);
		$this->id_alliance = $id;
	}
	public function actionReportArchive($id_planet, $moon = 0)
	{
		$this->template->archive = $this->glotrApi->getEspionageArchive($id_planet, $moon);
		$this->template->addPrefixToKeys = $this->glotrApi->addPrefixToKeys;
		$this->template->moon = $moon;

	}

	public function actionSystems($galaxy, $system)
	{
		$this->handlePermissions("perm_galaxyview");
		$galaxy = (int) $galaxy;
		$system = (int) $system;
		$maxGal = $this->glotrApi->getServerData("galaxies");
		$maxSys = $this->glotrApi->getServerData("systems");
		if($galaxy < 1 || $galaxy > $maxGal)
		{
			$this->flashMessage(sprintf($this->translator->translate("Galaxy must be a number from %d to %d"), 1, $maxGal), "error", true);
			$galaxy = 1;
		}
		if($system < 1 || $system > $maxSys)
		{
			$this->flashMessage(sprintf($this->translator->translate("System must be a number from %d to %d"), 1, $maxSys), "error", true);
			$system = 1;
		}
		$this->loadSearchResults(array("galaxy" => $galaxy, "system_start" => $system, "system_end" => $system));
		$this->template->dateTimeFormat = "j. n. Y H:i:s";
		$this->template->current = 0;
		$this->template->galaxy = $galaxy;
		$this->template->system = $system;
	}
	public function actionFleetMovements()
	{
		$vp = $this->getComponent("vp");
		$this->template->movements = $this->glotrApi->getFleetMovements($this->getUserPlayer(),$vp->getPaginator());
		if($this->isAjax())
		{
			$this->invalidateControl("fleetMovements");
		}
	}
	public function actionScoreHistory()
	{
		$sess = $this->getSession("scoreHistory");
		if(!$sess->selected)
			$sess->selected = array();
		$this->template->selected = $sess->selected;
		$this->template->scoreHistory = $this->glotrApi->getScoreHistory($sess->selected);
		$this->template->alliances =  $this->glotrApi->getTable("alliances")->order("tag")->fetchPairs("id_alliance_ogame", "tag");
		$this->template->players = $this->glotrApi->getTable("players")->order("playername")->fetchPairs("id_player_ogame", "playername");
		$this->template->periodDuration = $this->parameters["scoreHistoryPeriod"];
		$this->template->redraw = false;
	}
	public function actionScoreInactivity()
	{
		$vp = $this->getComponent("vp");
		$this->template->inactivity = $this->glotrApi->searchScoreInactivity($vp->getPaginator());
		if($this->isAjax())
		{
			$this->invalidateControl("inactivityList");
		}
	}
	public function scoreInactivityFormSubmitted($form)
	{
		$values = $form->getValues();
		$vp = $this->getComponent("vp");
		$this->template->inactivity = $this->glotrApi->searchScoreInactivity($vp->getPaginator(),$values);
		if($this->isAjax())
		{
			$this->invalidateControl("inactivityList");
		}
	}
	protected function createComponentScoreHistoryForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addMultiSelect("alliances", __("Alliances"),$this->template->alliances)
				->setTranslator(NULL);
		$form->addMultiSelect("players", __("Players"), $this->template->players)
				->setTranslator(NULL);
		$form->addSubmit("add", "Add");
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->scoreHistoryFormSubmitted;
		return $form;
	}
	public function scoreHistoryFormSubmitted($form)
	{
		$values = $form->getValues();

		$sess = $this->getSession("scoreHistory");
		foreach($values as $key => $val)
		{
			$items = $form->getComponent($key)->getItems();
			if(!empty($val))
			{
				foreach($val as $id)
				{
					$sess->selected[$key][$id] = $items[$id];
				}
			}
		}

		if($this->isAjax())
		{
			$this->scoreHistoryRefreshData($sess->selected);
		}
		else
			$this->redirect("this");
	}
	public function handleClearSelected()
	{
		$sess = $this->getSession("scoreHistory");
		$sess->selected = array();

		if($this->isAjax())
		{
			$this->scoreHistoryRefreshData($sess->selected);
		}
		else
			$this->redirect("this");
	}
	public function handleDeleteSelectedItem($id, $type)
	{
		$sess = $this->getSession("scoreHistory");
		if($type == "alliances")
		{
			unset($sess->selected["alliances"][$id]);
		}
		else
		{
			unset($sess->selected["players"][$id]);
		}
		if($this->isAjax())
		{
			$this->scoreHistoryRefreshData($sess->selected);
		}
		else
			$this->redirect("this");
	}
	protected function scoreHistoryRefreshData($search)
	{
			$this->template->selected = $search;
			$this->template->scoreHistory = $this->glotrApi->getScoreHistory($search);
			$this->template->redraw = true;
			$this->invalidateControl("scoreHistoryCharts");
			$this->invalidateControl("scoreHistorySelected");
	}
	protected function createComponentScoreInactivityForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addText("galaxy", "Galaxy:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Galaxy must be a number!")
					->addRule(Form::RANGE, "Galaxy must be a number from %d to %d", array(1, $this->glotrApi->getServerData("galaxies")) );
		$form->addText("system_start", "Start system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->glotrApi->getServerData("systems")) );
		$form->addText("system_end", "End system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->glotrApi->getServerData("systems")) );
		$form->addText("score_0_position_s", "Position(total) from:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("score_0_position_e", "Position(total) to:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("results_per_page", "Results per page:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Results per page must be a number!");
		$form->addSubmit("search", "Search");
		$form->setDefaults(array(
				"results_per_page" => 50
			));

		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->scoreInactivityFormSubmitted;
		return $form;
	}
	protected function createComponentSearchForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addText("alli_tag", "Alliance tag:", 30);
		$form->addText("playername", "Player name:", 30);

		$form->addText("galaxy", "Galaxy:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Galaxy must be a number!")
					->addRule(Form::RANGE, "Galaxy must be a number from %d to %d", array(1, $this->glotrApi->getServerData("galaxies")) );
		$form->addText("system_start", "Start system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->glotrApi->getServerData("systems")) );
		$form->addText("system_end", "End system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->glotrApi->getServerData("systems")) );

		$form->addText("score_0_position_s", "Position(total) from:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("score_0_position_e", "Position(total) to:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");

		$form->addText("score_3_position_s", "Position(military) from:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("score_3_position_e", "Position(military) to:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");

		$form->addText("score_6_position_s", "Position(military destroyed) from:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("score_6_position_e", "Position(military destroyed) to:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");

		$form->addText("score_7_position_s", "Position(honor) from:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addText("score_7_position_e", "Position(honor) to:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Position must be a number!");
		$form->addRadioList("with_moons", "Only planets with moons:", array(1 => "yes", 0 => "no"))
				->getSeparatorPrototype()->setName(NULL);
		$cont = $form->addContainer("statuses");

		$cont->addCheckbox("all", "All");
		$cont->addCheckbox("inactives", "Inactives");
		$cont->addCheckbox("vmode", "V-mode");
		$cont->addCheckbox("banned", "Banned");
		$cont->setDefaults(array("all" => true));

		$form->addText("results_per_page", "Results per page:", 5)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "Results per page must be a number!");
		$form->addRadioList("order_by", "Order by:", array("coords" => "Coordinates","player"=> "Playername","tag" => "Alliance tag"))
				->getSeparatorPrototype()->setName(NULL);
		$form->addRadioList("order_direction", "", array("asc" => "Ascendant", "desc" => "Descendant"))
				->getSeparatorPrototype()->setName(NULL);

		$form->addSubmit("search", "Search");

		$values = $this->getSession("searchForm")->searchFormValues;
		if(empty($values))
			$form->setDefaults(array(
				"with_moons" => 0,
				"order_by" => "coords",
				"order_direction" => "asc",
				"results_per_page" => 20
			));
		else
		{
			$form->addButton("reset", "Reset filter")
					->setAttribute("onclick", "$.ajax('".$this->link("resetSearchForm!")."');");


			$form->setDefaults($values);
		}


		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->searchFormSubmitted;
		return $form;
	}
	public function searchFormSubmitted($form)
	{
		$values = $form->getValues();
		if(!$values["results_per_page"])
			$values["results_per_page"] = 20;
		// store values for search form in session
		$sess = $this->getSession("searchForm");



		if(!$form->offsetExists("reset"))
			$form->addButton("reset", "Reset filter")
						->setAttribute("onclick", "$(this).ajax(".$this->link("resetSearchForm!").");");

		$this->loadSearchResults($values);
		$sess->searchFormValues = $form->getValues();
	}
	public function handleResetSearchForm()
	{

		$sess = $this->getSession("searchForm");
		unset($sess->searchFormValues);
		if($this->isAjax())
			$this->invalidateControl("searchForm");
		else
			$this->redirect("this");
	}
	public function handlePlayerStatusChange($status, $mode = "local")
	{
		$this->glotrApi->setPlayerStatus($this->id_player, $status, $mode, $this->getUser()->getIdentity()->id_user);
		if($this->isAjax())
		{
			$this->invalidateControl("playerStatus");
			$this->template->results = $this->glotrApi->getPlayerDetail($this->id_player, $this->getUserPlayer());
		}
		else
		{
			$this->redirect("this");
		}
	}
	protected function createComponentPlayerInfoRow()
	{
		$control = new GLOTR\Components\PlayerInfoRow;
		$control->injectPlanetInfoFactory(new \Nette\Callback($this, "createComponentPlanetInfo"));
		$control->injectTranslator($this->translator);
		return $control;
	}
	public function createComponentPlanetInfo()
	{
			$control = new GLOTR\Components\PlanetInfo;
			$control->injectUserPlayer($this->getUserPlayer());
			$control->injectAuthenticator($this->authenticator);
			$control->injectEspionageKeys($this->glotrApi->getEspionageKeys());
			$control->injectFilterDataCallback($this->glotrApi->filterData);
			$control->injectTranslator($this->translator);
			$control->injectServerData($this->glotrApi->getServerData());
			return $control;

	}
	protected function createComponentFleetMovement()
	{
			$control = new GLOTR\Components\FleetMovement;
			$control->injectTranslator($this->translator);
			$control->injectFleetKeys($this->glotrApi->getFleetKeys());
			return $control;
	}
	public function createComponentVp()
	{

		$vp = new \VisualPaginator($this, "vp");
		$vp->setTranslator($this->translator);
		return $vp;
	}
	protected function createComponentManualActivityForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addDatePicker("activity_date", "Activity date")
				->addRule(Form::FILLED, "Activity date must be filled!");
		$form->addText("hour", "Hour")
				->addRule(Form::FILLED, "Hour must be filled!")
				->addRule(Form::RANGE, "Hour must be in range from %d to %d", array(0,23));
		$form->addText("minute", "Minute")
				->addRule(Form::FILLED, "Minute must be filled!")
				->addRule(Form::RANGE, "Minute must be in range from %d to %d", array(0,59));

		$form->addRadioList("type", "Activity type", array("inactivity" => "Inactivity", "activity" => "Activity"))
				->addRule(Form::FILLED, "Please choose activity type")
				->getSeparatorPrototype()->setName(NULL);
		$form->addSubmit("send_activity", "Send");
		$form->setDefaults(array("type" => "activity"/*, "activity_date" => date(\JanTvrdik\Components\DatePicker::W3C_DATE_FORMAT)*/));
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->manualActivityFormSubmitted;
		return $form;
	}
	public function manualActivityFormSubmitted($form)
	{
		$values = $form->values;
		$type = "manual";
		if($values["type"] == "inactivity")
			$type = "manual_inactivity";

		$act = array(
						"id_player" => $this->id_player,
						"timestamp" => $values["activity_date"]->getTimestamp() + 3600*$values["hour"] + 60*$values["minute"],
						"type" => $type
					);

		$this->glotrApi->insertActivity($act);

		if($this->isAjax())
		{
			$this->invalidateControl("manualActivityForm");
			$this->invalidateControl("activityChart");
			$this->getComponent("activityChart")->redraw = true;
			$tmp = $this->glotrApi->getPlayerDetail($this->id_player, array(), array("activity"));
			$this->template->results["activity"] = $tmp["activity"];
		}
		else
		{
			$this->redirect("this");
		}
	}
	protected function createComponentSystemsControlsForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addText("galaxy", "Galaxy", 3)
				->addRule(Form::INTEGER, "Galaxy must be a number!");
		$form->addText("system", "System", 3)
				->addRule(Form::INTEGER, "System must be a number!");
		$form->addSubmit("show", "Show");

		$form->setTranslator($this->translator);
		$form->setDefaults(array("galaxy" => 1, "system" => 1));
		$form->setValues(array("galaxy" => $this->template->galaxy, "system" => $this->template->system));

		$form->onValidate[] = $this->validateSystemsControlsForm;
		$form->onSuccess[] = $this->systemsControlsFormSubmitted;
		return $form;
	}
	public function validateSystemsControlsForm($form)
	{
		$values = $form->values;
		$maxGal = $this->glotrApi->getServerData("galaxies");
		$maxSys = $this->glotrApi->getServerData("systems");
		if(!isset($values["galaxy"]) || $values["galaxy"] >= $maxGal)
			$values["galaxy"] = 1;
		elseif($values["galaxy"] < 1)
			$values["galaxy"] = $maxGal;

		if(!isset($values["system"]) || $values["system"] >= $maxSys)
			$values["system"] = 1;
		elseif($values["system"] < 1)
			$values["system"] = $maxSys;
		$form->setValues($values);
	}
	public function systemsControlsFormSubmitted($form)
	{
		$values = $form->values;
		if($this->isAjax())
		{
			$this->invalidateControl("systemsControlsForm");
			$this->invalidateControl("systemsResults");

			$this->loadSearchResults(array("galaxy" => $values["galaxy"], "system_start" => $values["system"], "system_end" => $values["system"]));
			$this->template->current = 0;
			$this->template->galaxy = $values["galaxy"];
			$this->template->system = $values["system"];
		}
		else
		{
			$this->redirect("this");
		}

	}
	protected function createComponentActivityChart()
	{
		$control = new GLOTR\Components\ActivityChart;
		$control->injectTranslator($this->translator);
		$control->injectActivityTypes($this->glotrApi->getActivityTypes());
		return $control;
	}
	protected function createComponentAllianceMembersChart()
	{
		$control = new GLOTR\Components\AllianceMembersCharts;
		$control->injectTranslator($this->translator);
		return $control;
	}
	protected function createComponentScoreHistoryCharts()
	{
		$control = new GLOTR\Components\ScoreHistoryCharts;
		$control->injectParameters($this->parameters);
		$control->injectTranslator($this->translator);
		return $control;
	}
	protected function createComponentActivityFilterForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");

		$form->addDatePicker("activity_start", "From");
		$form->addDatePicker("activity_end", "To");
		$container = $form->addContainer("types");
		foreach($this->glotrApi->getActivityTypes() as $type):
			$container->addCheckbox("$type", $type)->setDefaultValue(TRUE);
		endforeach;
		$container = $form->addContainer("days");
		$days = array(1 => "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" );
		foreach($days as $key => $day)
		{
			$container->addCheckbox("$key", $day)->setDefaultValue(TRUE);
		}

		$form->addSubmit("filter", "Filter");
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->activityFilterFormSubmitted;
		return $form;
	}
	public function activityFilterFormSubmitted($form)
	{
		$values = $form->values;
		$filter = array();
		//dump($values);
		if($values["activity_start"])
		{
			$filter["timestamp >= ?"] = $values["activity_start"]->getTimestamp();
		}
		if($values["activity_end"])
		{
			$filter["timestamp <= ?"] = $values["activity_end"]->getTimestamp();
		}
		if($values["days"])
		{
			$tmp = array();
			foreach($values["days"] as $k => $day)
				if($day)
					$tmp[] = $k;
			$filter["dayofweek(from_unixtime(timestamp))"] = $tmp;
		}
		if($values["types"])
		{
			$tmp = array();
			foreach($values["types"] as $k => $type)
				if($type)
					$tmp[] = $k;
			$filter["type"] = $tmp;
		}
		if($this->isAjax())
		{
			$this->invalidateControl("activityChart");
			$this->getComponent("activityChart")->redraw = true;
			$this->template->results["activity"] = $this->glotrApi->getPlayerDetail($this->id_player, array(), array("activity"), $filter);
			$this->invalidateControl("activityTextView");
			$this->template->results["activity_all"] = $this->glotrApi->getPlayerDetail($this->id_player, array(), array("activity_all"), $filter);
		}
	}
	protected function createComponentPlayerNotesForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addTextArea("notes", "Notes:")
				->setDefaultValue($this->template->results["player"]["notes"]);
		$form->addSubmit("save", "Save");
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->playerNotesFormSubmitted;
		return $form;
	}
	public function playerNotesFormSubmitted($form)
	{
		$values = $form->values;
		$this->glotrApi->getTable("players")->where(array("id_player_ogame" => $this->id_player))->update(array("notes" => ($values["notes"]) ? $values["notes"] : ""));
		if($this->isAjax())
		{
			$this->invalidateControl("playerNotesForm");
		}
		else
		{
			$this->redirect("this");
		}
	}
	protected function createComponentPlayerFSForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addGroup("Start");
		$form->addDatePicker("start_date", "Date")
				->addRule(Form::FILLED, "Start must be filled!");
		$form->addText("start_hour", "Hour", 2)
				->addRule(Form::RANGE, "Hour must be from %d to %d", array(0,23))
				->addRule(Form::FILLED, "Start must be filled!");
		$form->addText("start_minute", "Minute", 2)
				->addRule(Form::RANGE, "Minute must be from %d to %d", array(0,59))
				->addRule(Form::FILLED, "Start must be filled!");
		$form->addGroup("End");
		$form->addDatePicker("end_date", "Date")
				->addRule(Form::FILLED, "End must be filled!");;
		$form->addText("end_hour", "Hour", 2)
				->addRule(Form::RANGE, "Hour must be from %d to %d", array(0,23))
				->addRule(Form::FILLED, "End must be filled!");;
		$form->addText("end_minute", "Minute", 2)
				->addRule(Form::RANGE, "Minute must be from %d to %d", array(0,59))
				->addRule(Form::FILLED, "End must be filled!");;
		$form->addGroup("Optional");
		$form->addText("precision", "Precision (in minutes)", 3);
		$form->addTextArea("note", "Note:")	;

		$form->addSubmit("save", "Save");
		$form->setTranslator($this->translator);
		$form->onSuccess[] = $this->playerFSFormSubmitted;
		return $form;
	}
	public function playerFSFormSubmitted($form)
	{
		$values = $form->values;
		$data = array(
			"start" => $values["start_date"]->getTimestamp() + 3600*$values["start_hour"] + 60*$values["start_minute"],
			"end" => $values["end_date"]->getTimestamp() + 3600*$values["end_hour"] + 60*$values["end_minute"],
			"id_player" => $this->id_player,
			"precision" => $values["precision"],
			"note" => $values["note"]
		);
		if($data["start"] >= $data["end"])
			throw new \Nette\Application\BadRequestException("FS start must be a date smaller than FS end!");
		$this->glotrApi->insertFS($data);
		if($this->isAjax())
		{
			$this->invalidateControl("fsTable");
			$this->template->results["fs"] = $this->glotrApi->getPlayerDetail($this->id_player, array(), array("fs"));
		}
		else
		{
			$this->redirect("this");
		}

	}
	protected function loadSearchResults($search)
	{
			$vp = $this->getComponent("vp");
			$paginator = $vp->getPaginator();

			$this->template->results = $this->glotrApi->searchUniverse($search, $paginator);

			$this->getRelativeStatusForResults($this->template->results);
			if($this->isAjax())
			{
				$this->invalidateControl ("planetList");
				$this->invalidateControl ("searchForm");
				$this->invalidateControl ("paginator");
				$this->invalidateControl ("planetInfo");
			}
	}
	/**
	 * Get user´s player data
	 * @return array
	 */
	protected function getUserPlayer()
	{
		if(!isset($this->userPlayerData))
		{
			$this->userPlayerData = $this->glotrApi->getPlayerDetail($this->getUser()->getIdentity()->id_player);
			$this->userPlayerData = $this->userPlayerData["player"];
			$this->userPlayerData["id_user"] = $this->getUser()->getIdentity()->id_user;
		}

		return $this->userPlayerData;
	}
	protected function getRelativeStatusForResults(&$results)
	{
		$player2 = $this->getUserPlayer();
		foreach($results as &$r)
		{
			$r["_computed_player_status"] = $this->glotrApi->getRelativePlayerStatus($r, $player2);
			$r["_computed_status_class"] = $this->glotrApi->getClassForPlayerStatus($r["_computed_player_status"]);
		}
	}
}