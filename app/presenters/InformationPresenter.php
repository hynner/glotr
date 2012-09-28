<?php


use Nette\Application\UI\Form,
	Nette\Security as NS;

class InformationPresenter extends BasePresenter
{
	protected $id_player;
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
		if(!$id)
			throw new Nette\Application\BadRequestException;
		$this->template->results = $this->context->players->search($id);
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
		$this->id_player = $id;
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
					->addRule(Form::RANGE, "Galaxy must be a number from %d to %d", array(1, $this->context->server->getGalaxies()) );
		$form->addText("system_start", "Start system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->context->server->getSystems()) );
		$form->addText("system_end", "End system:", 4)
				->addCondition(Form::FILLED)
					->addRule(Form::INTEGER, "System must be a number!")
					->addRule(Form::RANGE, "System must be a number from %d to %d", array(1, $this->context->server->getSystems()) );

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


		$form->setTranslator($this->context->translator);
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
		$sess->searchFormValues = $values;
		if(!$form->offsetExists("reset"))
			$form->addButton("reset", "Reset filter")
						->setAttribute("onclick", "$(this).ajax(".$this->link("resetSearchForm!").");");
		$this->loadSearchResults($values);


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
	protected function createComponentPlanetInfo()
	{
			$control = new GLOTR\PlanetInfo;
			$control->setContext($this->context);
			return $control;

	}
	public function createComponentVp()
	{

		return new \VisualPaginator($this, "vp");
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
		$form->setTranslator($this->context->translator);
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

		$this->context->activities->insertActivity($act);

		if($this->isAjax())
		{
			$this->invalidateControl("manualActivityForm");
			$this->invalidateControl("activityChart");
			$this->getComponent("activityChart")->redraw = true;
			$this->template->results["activity"] = $this->context->activities->search($this->id_player);
		}
		else
		{
			$this->redirect("this");
		}
	}
	protected function createComponentActivityChart()
	{
		$control = new GLOTR\ActivityChart;
		$control->setContext($this->context);
		return $control;
	}
	protected function createComponentActivityFilterForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");

		$form->addDatePicker("activity_start", "From");
		$form->addDatePicker("activity_end", "To");
		$container = $form->addContainer("types");
		foreach($this->context->activities->types as $type):
			$container->addCheckbox("$type", $type)->setDefaultValue(TRUE);
		endforeach;
		$container = $form->addContainer("days");
		$days = array(1 => "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" );
		foreach($days as $key => $day)
		{
			$container->addCheckbox("$key", $day)->setDefaultValue(TRUE);
		}

		$form->addSubmit("filter", "Filter");
		$form->setTranslator($this->context->translator);
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
			$this->template->results["activity"] = $this->context->activities->search($this->id_player, $filter);
			$this->invalidateControl("activityTextView");
			$this->template->results["activity_all"] = $this->context->activities->searchUngrouped($this->id_player, $filter);
		}
	}
	protected function createComponentPlayerNotesForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addTextArea("notes", "Notes:")
				->setDefaultValue($this->template->results["player"]["notes"]);
		$form->addSubmit("save", "Save");
		$form->setTranslator($this->context->translator);
		$form->onSuccess[] = $this->playerNotesFormSubmitted;
		return $form;
	}
	public function playerNotesFormSubmitted($form)
	{
		$values = $form->values;
		$this->context->players->getTable()->where(array("id_player_ogame" => $this->id_player))->update(array("notes" => ($values["notes"]) ? $values["notes"] : ""));
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
		$form->setTranslator($this->context->translator);
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
		$this->context->fs->insertFS($data);
		if($this->isAjax())
		{
			$this->invalidateControl("fsTable");
			$this->template->results["fs"] = $this->context->fs->search($this->id_player);
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
			$this->template->results = $this->context->universe->search($search, $paginator);
			$player2 = $this->context->players->search($this->getUser()->getIdentity()->id_player);
			if(!empty($player2))
				$player2 = $player2["player"];

				foreach($this->template->results as &$r)
				{
					$r["_computed_player_status"] = $this->context->players->getRelativeStatus($r, $player2);
					$r["_computed_status_class"] = "";
					foreach($r["_computed_player_status"] as $key => $value)
					{

						if(is_integer($key))
							$r["_computed_status_class"] .= " ".$value;
						elseif(is_bool($value) && $value)
							$r["_computed_status_class"] .= " ".$key;
						elseif(!is_bool($value))
							$r["_computed_status_class"] .= " "."$key-$value";
					}
				}




			if($this->isAjax())
			{
				$this->invalidateControl ("planetList");
				$this->invalidateControl ("searchForm");
				$this->invalidateControl ("paginator");
			}
	}
}