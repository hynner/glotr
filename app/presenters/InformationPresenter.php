<?php


use Nette\Application\UI\Form,
	Nette\Security as NS;

class InformationPresenter extends BasePresenter
{
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

			$vp = $this->getComponent("vp");
			$paginator = $vp->getPaginator();
			$this->template->results = $this->context->universe->search($sess->searchFormValues, $paginator);
			if($this->isAjax())
			{
				$this->invalidateControl ("planetList");
				$this->invalidateControl ("searchForm");
				$this->invalidateControl ("paginator");
			}
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
		$vp = $this->getComponent("vp");
		$paginator = $vp->getPaginator();
		$paginator->page = 1; // reset pages

		$this->template->results = $this->context->universe->search($values, $paginator);
		if($this->isAjax())
		{
			$this->invalidateControl ("planetList");
			$this->invalidateControl ("searchForm");
			$this->invalidateControl ("paginator");

		}


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
	public function createComponentPlanetInfo()
	{
			$control = new GLOTR\PlanetInfo;
			$control->setContext($this->context);
			return $control;

	}
	public function createComponentVp()
	{

		return new \VisualPaginator($this, "vp");
	}
}