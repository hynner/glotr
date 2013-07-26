<?php
namespace FrontModule;
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
		$this->template->serverData = $this->glotrApi->getServerData();
		$this->template->numPlayers = $this->glotrApi->getCount("players");
		$this->template->numAllis = $this->glotrApi->getCount("alliances");
	}
	public function handleGetNewKey()
	{
		$key = $this->users->getNewKey($this->getUser()->getIdentity());
		if($this->isAjax())
			$this->invalidateControl("logonKey");
		else
			$this->redirect("this");

	}

}
