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
			$this->template->users[$r->id_user] = $r->toArray();
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
	protected function createComponentPermissionsForm()
	{
		$form = new GLOTR\MyForm;
		$form->onSuccess[] = $this->permissionsFormSubmitted;
		foreach($this->template->users as $user)
		{
			$cont = $form->addContainer("user".$user["id_user"]);
			foreach($this->permissions as $name => $label)
				$cont->addCheckbox($name, $label)
						->setDefaultValue($user[$name])
						->setTranslator(NULL); // permission labels are already translated

		}
		$form->addSubmit("set", "Set permissions");
		return $form;
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

	}

}
