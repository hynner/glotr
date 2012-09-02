<?php
namespace GLOTR;
use Nette\Application\UI\Form;

class UserRegistrationForm extends MyForm
{
	public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);


		$this->addText("username", "Username:", 30)
				->addRule(Form::FILLED, "Username must be filled!")
				->addRule(Form::MIN_LENGTH, "Username must have at least %d characters!" ,4 )
				->addRule(Form::MAX_LENGTH, "Username must have max %d characters!", 32);
		$this->addPassword("password", "Password:", 30)
				->addRule(Form::FILLED, "Password must be filled!")
				->addRule(Form::MIN_LENGTH, "Password must have at least %d characters!", 4);
		$this->addPassword("confirm_pass", "Password again:", 30)
				->addRule(Form::EQUAL, "Passwords must be the same!", $this["password"]);
		$this->addText("email", "E-mail:", 30)
				->addRule(Form::EMAIL, "Please provide valid e-mail address!");
		$this->addSubmit("register", "Register");
	}
}
