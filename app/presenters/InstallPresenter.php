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
		$conn = $this->users->getConnection();
		$db = unserialize(file_get_contents($this->context->parameters["dbSetupFile"]));
		$current = $this->getDatabaseStructure(true);
		$refresh = false;
		// if they are the same database is already fully updated
		if($current["tables"] != $db["tables"] || $current["columns"] != $db["columns"])
		{
			$refresh = true;
			foreach($db["tables"] as $name)
			{
				$tblName = $this->context->parameters["tablePrefix"].$name;
				if(in_array($name, $current["tables"]))
				{
					if($current["columns"][$name] != $db["columns"][$name])
					{
						foreach($db["columns"][$name] as $field => $col)
						{
							$query_end = $this->getColDefinition($col);
							if(!isset($current["columns"][$name][$field]))
							{

								$query = "Alter table $tblName ADD ";

								$conn->query($query.$query_end);
							}
							elseif($current["columns"][$name][$field] != $db["columns"][$name][$field])
							{
								$query = "Alter table $tblName CHANGE `$field` ";

								try
								{
									$conn->query($query.$query_end);
								}
								catch(\PDOException $e)
								{
									echo $e->getMessage();
								}

							}
						}
					}
				}
				else
				{
					$query = "Create table $tblName (";
					foreach($db["columns"][$name] as $col)
					{
						$query .= $this->getColDefinition($col). ",";
					}
					$query = substr($query, 0, -1);
					$query .= ")";
					$conn->query($query);
				}

			}

			if($refresh)
			{

				$current = $this->getDatabaseStructure(true);

			}

			foreach($db["tables"] as $name)
			{
				$tblName = $this->context->parameters["tablePrefix"].$name;
				// handle indexes
				foreach($db["indexes"][$name] as $key_name => $index)
				{
					if( !isset($current["indexes"][$name][$key_name]))
					{
						$query = "ALTER TABLE  `$tblName` ADD ".(($key_name == "PRIMARY") ? "PRIMARY" : "UNIQUE  `$key_name`")." (";
						foreach($index as $subindex)
						{
							$query .= " `$subindex->Column_name`,";
						}
						$query = substr($query, 0, -1);
						$query .= ")";
						$conn->query($query);
					}
				}

			}
		}
		$this->flashMessage("All tables are OK!");
		if($this->users->getAdminCount() == 0)
			$this->redirect("createAdmin");

	}
	protected function getColDefinition($col)
	{
		$query_end = " `$col->Field` $col->Type ".(($col->Null == "NO") ? "NOT" : "")." NULL";
		if(!is_null($col->Default))
			$query_end .= " DEFAULT $col->Default";
		if($col->Key == "UNI")
			$query_end .= " UNIQUE";
		elseif($col->Key == "PRI")
			$query_end .= " PRIMARY KEY";
		$query_end .= " $col->Extra";
		return $query_end;
	}
	protected function compareIndex($index1, $index2)
	{
		$same = true;
		$same = $same && ($index1->Non_unique == $index2->Non_unique);
		$same = $same && ($index1->Key_name  == $index2->Key_name );
		$same = $same && ($index1->Seq_in_index   == $index2->Seq_in_index  );
		$same = $same && ($index1->Column_name   == $index2->Column_name  );
		return $same;
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
		$save = $this->getDatabaseStructure();
		file_put_contents($this->context->parameters["dbSetupFile"], serialize($save));
	}
	protected function getDatabaseStructure($remove_table_prefix = true)
	{
		$conn = $this->users->getConnection();


		$res = $conn->query("show tables");
		$tables = array();
		while($r = $res->fetch())
			$tables[] =($remove_table_prefix)  ? preg_replace("/^". $this->context->parameters["tablePrefix"]."/", "", $r->offsetGet(0)) : $r->offsetGet(0);

		$columns = array();
		$indexes = array();
		foreach($tables as $table)
		{
			$res = $conn->query("show columns from $table");
				while($r = $res->fetch())
					$columns[$table][$r->Field] = $r;
			$res = $conn->query("show indexes from $table");
			while($r = $res->fetch())
					$indexes[$table][$r->Key_name][] = $r;
		}
		$save = array("tables" => $tables, "columns" => $columns, "indexes" => $indexes);
		return $save;
	}
}
