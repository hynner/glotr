<?php

use Nette\Application\UI,
	Nette\Security as NS;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{

	public function actionDefault()
	{
		$this->redirect("Sign:in");
	}
	public function actionIn()
	{
		if($this->getUser()->isLoggedIn())
			$this->redirect("Homepage:");
	}
	/**
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new GLOTR\MyForm;
		$form->addText('username', 'Username:')
			->setRequired('Please provide a username.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please provide a password.');

		$form->addCheckbox('remember', 'Remember me on this computer');

		$form->addSubmit('send', 'Sign in');
        $form->addButton("register", "Register")
            ->setAttribute("onclick", "window.location.href='".$this->link("register")."'");
		$form->addButton("forgotten", "Forgot password")
            ->setAttribute("onclick", "window.location.href='".$this->link("forgotPassword")."'");
		$form->onSuccess[] = $this->signInFormSubmitted;
		$form->setTranslator($this->context->translator);
		return $form;
	}


	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->getValues();
			if ($values->remember) {
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->getUser()->setExpiration('+ 30 minutes', TRUE);
			}
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Homepage:');

		} catch (NS\AuthenticationException $e) {
			$form->addError($this->context->translator->translate($e->getMessage()));
		}
	}
	/**
	 * Forgotten password form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentForgottenPasswordForm()
	{
		$form = new GLOTR\MyForm;
		$form->addText('username', 'Username:')
			->setRequired('Please provide a username.');

		$form->addText('email', 'E-mail:')
			->setRequired('Please provide an e-mail.');


		$form->addSubmit('send', 'Get new password');
		$form->addButton("back", "Back")
            ->setAttribute("onclick", "window.location.href='".$this->link("in")."'");
		$form->onSuccess[] = $this->forgottenPasswordFormSubmitted;
		$form->setTranslator($this->context->translator);
		return $form;
	}


	public function forgottenPasswordFormSubmitted($form)
	{
		try {
			$values = $form->getValues();
			$usr = $this->context->users->findOneBy(array("email" => $values["email"], "username" => $values["username"]));
			if($usr)
			{
				$template = new Nette\Templating\FileTemplate(APP_DIR."/templates/Emails/forgotPass-email.latte");
				$template->registerFilter(new Nette\Latte\Engine);
				$template->pass = Nette\Utils\Strings::random(12);
				$template->setTranslator($this->context->translator);

				$this->context->users->findOneBy(array("email" => $values["email"], "username" => $values["username"]))
						->update(array("password" => $this->context->authenticator->calculateHash($template->pass)));

				$mail = new Nette\Mail\Message;
				$mail->setFrom($this->context->parameters["adminEmail"])
						->addTo($values["email"])
						->setSubject($this->context->translator->translate("GLOTR password reset"))
						->setHtmlBody($template)
						->send();
				$this->flashMessage("Your password was reset! You should get the email with new password soon.", "success");
				$this->redirect("Sign:in");


			}
			else
			{
				$form->addError($this->context->translator->translate("Invalid username or e-mail!"));
			}


		}
		catch(\PDOException $e)
		{
			$this->flashMessage("Password update failed!", "error");
		}

	}



	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('in');
	}
	public function actionRegister()
	{

	}
	protected function createComponentRegistrationForm()
	{
		$form = new GLOTR\UserRegistrationForm();
		// player don´t have to be chosen, someone could be just admin without playing that server at all
		$form->addSelect("id_player", "Ingame nickname:", $this->context->players->getTable()->order("playername")->fetchPairs("id_player_ogame", "playername"))
				->setPrompt("Choose player")
				->setTranslator(NULL);
		$form->onSuccess[] = $this->registrationFormSubmitted;
		$form->setTranslator($this->context->translator);
		return $form;
	}
	public function registrationFormSubmitted($form)
	{
		$values = $form->values;
		try{
			$this->context->users->getTable()->insert(array(
				"username" => $values["username"],
				"password" => $this->context->authenticator->calculateHash($values["password"]),
				"email" => $values["email"],
				"is_admin" => 0, // new user isn´t admin
				"id_player" => $values["id_player"],
				"active" => 0 // admin must activate this acc

			));
			$this->flashMessage("Your registration was submitted, now you have to wait untill administrator accepts it!", "success");
			$this->redirect("Sign:in");
		}
		catch(PDOException $e)
		{

			$form->addError("Username already exists, please choose different one!");
		}

	}

}
