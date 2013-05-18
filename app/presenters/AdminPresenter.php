<?php
use Nette\Application\UI\Form;
class AdminPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{

			$this->redirect("Sign:in");
		}

	}

	public function actionUsers()
	{
		$this->handlePermissions(array("perm_user_mng"));
		$this->loadUsers2Template();
	}
	public function actionPermissions()
	{
		$this->handlePermissions(array("perm_perm_mng"));
		$this->loadUsers2Template();
		$this->template->permissions = $this->permissions;
	}
	public function actionSyncSetup()
	{
		if($this->context->parameters["enableSync"] === FALSE)
		{
			$this->flashMessage("Synchronization is disabled!", "error");
			$this->redirect("Homepage:");
		}
		$this->handlePermissions(array("perm_sync_mng"));
		$this->loadSyncServers2Template();
	}
	public function handletoggleActive($id)
	{
		$usr = $this->context->users->find($id);
		if($usr !== FALSE)
		{
			$active = (int) !((bool) $usr["active"]); // toggle active

			$this->context->users->getTable()->where(array("id_user" => $id))->update(array("active" => $active));
			if($this->isAjax())
			{
				$this->loadUsers2Template();
				$this->invalidateControl("userTable");
			}
			else
			{
				$this->redirect("this");
			}
		}

	}
	protected function loadUsers2Template()
	{
		$this->template->users = array();
		$res =  $this->context->users->findAll();
		while($r = $res->fetch())
		{
			$this->template->users[$r->id_user] = $r->toArray();
			if($r->id_player)
				$this->template->users[$r->id_user]["player"] = $this->context->players->findOneBy(array("id_player_ogame" => $r->id_player));
			else
				$this->template->users[$r->id_user]["player"] = "";
		}

	}
	protected function loadSyncServers2Template()
	{
		$this->template->servers = $this->context->syncServers->getTable()->fetchPairs("id_server");
	}
	public function handledelete($id)
	{
		$this->context->users->getTable()->where(array("id_user" => $id))->delete();
		if($this->isAjax())
		{
			$this->loadUsers2Template();
			$this->invalidateControl("userTable");
		}
		else
		{
			$this->redirect("this");
		}
	}
	public function handleDeleteSyncServer($id)
	{
		$this->context->syncServers->getTable()->where(array("id_server" => $id))->delete();
		if($this->isAjax())
		{
			$this->loadSyncServers2Template();
			$this->invalidateControl("syncList");
		}
		else
		{
			$this->redirect("this");
		}
	}
	public function handleVerifySyncServer($id)
	{
		$this->context->syncServers->verify($id);
		if($this->isAjax())
		{
			$this->loadSyncServers2Template();
			$this->invalidateControl("syncList");
		}
		else
		{
			$this->redirect("this");
		}
	}
	public function handleSyncServerDeactivate($id)
	{
		$this->context->syncServers->getTable()->where("id_server", $id)->update(array("active" =>"0"));
		if($this->isAjax())
		{
			$this->loadSyncServers2Template();
			$this->invalidateControl("syncList");
		}
		else
		{
			$this->redirect("this");
		}
	}
	protected function createComponentPermissionsForm()
	{
		$form = new GLOTR\MyForm;
		$form->onSuccess[] = $this->permissionsFormSubmitted;
		foreach($this->template->users as $user)
		{
			$cont = $form->addContainer("user".$user["id_user"]);
			foreach($this->permissions as $name => $label)
				$cont->addCheckbox($name, $label)
						->setDefaultValue(($user["is_admin"] == 1) ? true : $user[$name])
						->setDisabled($user["is_admin"] == 1)
						->setTranslator(NULL); // permission labels are already translated

		}
		$form->addSubmit("set", "Set permissions");
		return $form;
	}

	protected function createComponentSyncAddForm()
	{
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");

		$form->onSuccess[] = $this->addSyncFormSubmitted;
		$form->addText("servername", "Server name")
				->addRule(Form::LENGTH, "Server name must from %d to %d long!", array(3,30));
		$form->addText("username", "Username")
				->addRule(Form::LENGTH, "Username must from %d to %d long!", array(3,30));
		$form->addText("url", "URL")
				->addRule(Form::MAX_LENGTH, "URL lenght can be at most %d!", 255)
				->addRule(Form::FILLED, "URL must be filled!")
				->addRule(Form::URL, "URL must be valid!");
		$form->addText("password", "Password")
				->addRule(Form::LENGTH, "Password must from %d to %d long!", array(3,30));
		$form->addCheckbox("not_register", "Account is already created on sync server");

		$form->setDefaults(array(
			"password" => Nette\Utils\Strings::random(16, "0-9a-zA-Z")
		));

		$form->addSubmit("add", "Add server");
		$form->setTranslator($this->context->translator);
		return $form;
	}
	public function addSyncFormSubmitted($form)
	{
		$values = $form->getValues();
		$success = false;
		// remove protocol from url
		try
		{
			$success = $this->context->syncServers->register($values);
		}
		catch(\Nette\Application\ApplicationException $e)
		{
			// don´t translate these errors, they are variable
			$form->addError($e->getMessage(), false);
		}
		if($success)
		{
			$this->flashMessage ("New synchronization server added!", "success");
			$this->loadSyncServers2Template();
			$form->setValues(array("password" => Nette\Utils\Strings::random(16, "0-9a-zA-Z"), "not_register" => false), true);
		}
		if($this->isAjax())
		{
			$this->invalidateControl("syncForm");
			$this->invalidateControl("syncList");
		}
		else
		{
			$this->redirect("this");
		}

	}
	public function permissionsFormSubmitted($form)
	{
		$values = $form->getValues();
		foreach($values as $id => $perms)
		{
			$id = (int) str_replace("user", "", $id);
			foreach($perms as &$perm)
				$perm = (int) $perm;
			$this->context->users->setPermissions($id, $perms);
		}
		$this->flashMessage("Users permissions has been changed!", "success");

		if($this->isAjax())
		{
			$this->invalidateControl("permissionsForm");
		}
		else
		{
			$this->redirect("this");
		}

	}

}
