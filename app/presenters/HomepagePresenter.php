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
		$this->template->serverData = $this->context->server->getData();
		$this->template->numPlayers = $this->context->players->getCount();
		$this->template->numAllis = $this->context->alliances->getCount();
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
