<?php
use Nette\Application\UI\Form;
class InstallPresenter extends BasePresenter
{
	/** @var \GLOTR\Users */
	protected $users;

	protected function startup()
	{
		parent::startup();
		$this->users = $this->context->users;


	}
	public function actionDefault()
	{
		if($this->users->getAdminCount() == 0)
			$this->redirect("createAdmin");
	}
	protected function createComponentUserRegistrationForm()
	{
		$form = new GLOTR\UserRegistrationForm();

		$form->onSuccess[] = $this->UserRegistrationFormSubmitted;
		return $form;
	}
	public function UserRegistrationFormSubmitted($form)
	{
		if($this->users->getAdminCount() > 0):
			$this->flashMessage ("Only one admin account can be created via install script!", "error");
		else:
		$values = $form->values;
		try{
			$this->users->getTable()->insert(array(
				"username" => $values["username"],
				"password" => $this->context->authenticator->calculateHash($values["password"]),
				"is_admin" => 1,
				"active" => 1
			));
			$this->redirect("this");
		}
		catch(PDOException $e)
		{
			
			$form->addError("Username already exists, please choose different one!");
		}
		endif;

	}

}
