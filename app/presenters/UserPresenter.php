<?php
use Nette\Application\UI\Form;
class UserPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();
		if(!$this->getUser()->isLoggedIn())
		{

			$this->redirect("Sign:in");
		}

	}

	public function actionUserSettings()
	{

	}
	protected function createComponentUserSettingsForm()
	{
		$user = $this->getUser()->getIdentity();
		$form = new GLOTR\MyForm;
		$form->getElementPrototype()->class("ajax");
		$form->addPassword("oldPass", "Old password", 12)
				->addRule(Form::FILLED, "Verify the setting change with your password please.");
		$form->addPassword("newPass", "New password", 12)
				->addCondition(Form::FILLED)
					->AddRule(Form::MIN_LENGTH, "Password must have at least %d characters!", 4);
		$form->addPassword("newPassConfirm", "Confirm new password", 12)
				->addRule(Form::EQUAL, "Confirmation password must be the same as new password!", $form["newPass"]);
		$form->addText("email", "Email address", 30)
				->setDefaultValue($user->email)
				->addCondition(Form::FILLED)
					->AddRule(Form::EMAIL, "You must enter valid email address!");
		$form->addSelect("id_player", "Ingame nickname:", $this->context->players->getTable()->order("playername")->fetchPairs("id_player_ogame", "playername"))
				->setPrompt("Choose player")
				->setTranslator(NULL)
				->setDefaultValue($user->id_player);
		$form->addSelect("timezone", "Your timezone", DateTimeZone::listIdentifiers())
				->setPrompt("Use ogame server timezone")
				->setDefaultValue(array_search(date_default_timezone_get(), DateTimeZone::listIdentifiers()));
		$form->addSelect("lang", "Language", $this->context->parameters["langs"])
				->setDefaultValue(($user->lang) ? $user->lang : $this->context->parameters["lang"]);

		$form->addSubmit("save", "Save");
		$form->onSuccess[] = $this->userSettingsFormSubmitted;
		$form->onValidate[] = $this->userSettingsFormValidate;
		return $form;
	}
	public function userSettingsFormSubmitted($form)
	{
		$values = $form->getValues();
		$params = array();
		if($values["newPass"])
		{
			$params["password"] = GLOTR\Authenticator::calculateHash($values["newPass"]);
		}
		$params["email"] = $values["email"];
		$params["id_player"] = $values["id_player"];
		$timezones = DateTimeZone::listIdentifiers();
		$params["timezone"] = (!is_null($values["timezone"])) ? $timezones[$values["timezone"]] : "";
		$params["lang"] = $values["lang"];
		$this->context->users->getTable()->where("id_user", $this->getUser()->getIdentity()->id)->update($params);
		$this->flashMessage("Your settings was saved!", "success");
		$this->setUserParams($params);
		if($this->isAjax())
		{
			$this->invalidateControl("userSettingsForm");
			$this->invalidateControl("flashMessages");
		}
		else
			$this->redirect("this");

	}
	public function userSettingsFormValidate($form)
	{
		$values = $form->getValues();
		$tmp = $this->context->users->find($this->getUser()->getIdentity()->id_user);
		if($tmp->password != GLOTR\Authenticator::calculateHash($values["oldPass"], $tmp->password))
				$form->addError("Wrong old password!");
		if($this->isAjax())
		{
			$this->invalidateControl("userSettingsForm");
			$this->invalidateControl("flashMessages");
		}
	}
}
