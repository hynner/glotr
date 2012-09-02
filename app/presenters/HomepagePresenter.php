<?php

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	protected function startup()
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
			$this->redirect("Sign:in");
	}
	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
		//$this->context->server->updateFromApi();
		//$this->context->universe->updateFromApi();
		//$this->context->players->updateFromApi();
		//$this->context->universe->updateFromApi(2,0);
		//$this->context->highscore->cleanUpScoreHistory();
	}
	public function handleGetNewKey()
	{
		$key = $this->context->users->getNewKey($this->getUser()->getIdentity());
		if($this->isAjax())
			$this->invalidateControl("logonKey");
		else
			$this->redirect("this");

	}

}
