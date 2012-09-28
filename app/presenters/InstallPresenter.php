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
		$tables = unserialize(file_get_contents($this->context->parameters["tablesFile"]));
		$columns = unserialize(file_get_contents($this->context->parameters["columnsFile"]));
		$indexes = unserialize(file_get_contents($this->context->parameters["indexesFile"]));
		
		/*if($this->users->getAdminCount() == 0)
			$this->redirect("createAdmin");*/

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

	public function actionSaveDatabase()
	{
		$conn = $this->users->getConnection();


		$res = $conn->query("show tables");
		$tables = array();
		while($r = $res->fetch())
			$tables[] = preg_replace("/^". $this->context->parameters["tablePrefix"]."/", "", $r->offsetGet(0));

		$columns = array();
		$indexes = array();
		foreach($tables as $table)
		{
			$res = $conn->query("show columns from $table");
				while($r = $res->fetch())
					$columns[$table][] = $r;
			$res = $conn->query("show indexes from $table");
			while($r = $res->fetch())
					$indexes[$table] = $r;
		}

		file_put_contents($this->context->parameters["tablesFile"], serialize($tables));
		file_put_contents($this->context->parameters["columnsFile"], serialize($columns));
		file_put_contents($this->context->parameters["indexesFile"], serialize($indexes));
	}
}
