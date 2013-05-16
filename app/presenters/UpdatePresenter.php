<?php


use Nette\Application\UI\Form,
	Nette\Security as NS;


/**
 * Sign in/out presenters.
 */
class UpdatePresenter extends BasePresenter
{
	protected $userIdentity;
	protected function beforeRender()
	{

	}
	protected function startup()
	{
		$check = true;
		parent::startup();
		// this is for galaxyplugin validation
		$post = $this->context->httpRequest->getPost();
		//Nette\Diagnostics\Debugger::log(serialize($post));
		// is it galaxyplugin request?
		if(isset($post["type"]))
		{
			//only validate sends token separately
			if($post["type"] == "validate")
			{
				$token = $post["token"];
			}
			// everything else should have token in xml
			elseif($post["content"])
			{
				$xml = simplexml_load_string($post["content"]);
				$token = (string) $xml->header["token"];
			}
				if($token)
				{
					$check = false;
					try{
						$this->userIdentity = $this->context->authenticator->authenticateByLogonKey($token);
						//$this->getUser()->getStorage()->setAuthenticated(true);
						//$this->getUser()->setExpiration("+30 minutes");
					}
					catch(NS\AuthenticationException $e)
					{
						echo "User not found";
						Nette\Diagnostics\Debugger::$bar = FALSE;
						exit();
					}

				}
		}
		if($check && !$this->getUser()->isLoggedIn())
		{
			$this->redirect("Sign:in");
		}

	}
	public function actionUpdateAll()
	{
		$response = array("status" => "ok");
		try
		{
			if($this->context->players->needApiUpdate())
			{
				$this->context->players->updateFromApi();
				if($this->context->players->needApiUpdate())
					$response = array("status" => "continue", "what" => $this->context->translator->translate("players"));
				else
					$response = array("status" => "continue");
				goto send;
			}
			if($this->context->alliances->needApiUpdate())
			{
				$this->context->alliances->updateFromApi();
				if($this->context->alliances->needApiUpdate())
					$response = array("status" => "continue", "what" => $this->context->translator->translate("alliances"));
				else
					$response = array("status" => "continue");
				goto send;
			}

			if($this->context->universe->needApiUpdate())
			{
				$this->context->universe->updateFromApi();
				if($this->context->universe->needApiUpdate())
					$response = array("status" => "continue", "what" => $this->context->translator->translate("universe"));
				else
					$response = array("status" => "continue");
				goto send;
			}
			if($this->context->highscore->needApiUpdate())
			{
				$this->context->highscore->updateFromApi();
				$response = array("status" => "continue");
				goto send;
			}
			if($this->context->server->needApiUpdate())
			{
				$this->context->server->updateFromApi();
				$response = array("status" => "continue");
				goto send;
			}
			if($this->context->sync->needUpdate())
			{
				$this->context->sync->update();
				if($this->context->sync->needUpdate())
					$response = array("status" => "continue", "sync" => 1);
				else
					$response = array("status" => "continue");
				goto send;
			}

		}
		catch(Nette\Application\ApplicationException $e)
		{
			// allow_url_fopen disable, we need client to upload xml files himself
			$response = array(
				"status" => "failed",
				"what" => array(
					$this->context->universe->ogameApiGetFileNeeded(),
					$this->context->alliances->ogameApiGetFileNeeded(),
					$this->context->players->ogameApiGetFileNeeded()
					),
				"message" => $e->getMessage()
				);
			$response["what"] = array_merge($response["what"], $this->context->highscore->ogameApiGetFileNeeded());
		}

		send:
			$this->sendResponse(new Nette\Application\Responses\JsonResponse($response));
	}
	protected function anythingNeedsUpdate()
	{
		$cond = ($this->context->players->needApiUpdate() || $this->context->alliances->needApiUpdate() || $this->context->universe->needApiUpdate());

		return $cond;
	}
	public function actionGalaxyplugin()
	{
		$post = $this->context->httpRequest->getPost();
		$xml = new SimpleXMLElement("<?"."xml version=\"1.0\" encoding=\"UTF-8\"?>\n <galaxytool universe=\"".str_replace("http://", "", $this->context->parameters["server"])."\">\n</galaxytool>");
		$version = $xml->addChild("version");
		$version->addAttribute("major", 4);
		$version->addAttribute("minor", 9);
		$version->addAttribute("revision", 2);
		$version->addAttribute("glotr_version", $this->context->parameters["version"]);
		if(isset($post["type"]) && ($post["type"] == "validate" || isset($post["content"])))
		{
			if($post["type"] == "validate")
			{
				$permission = $xml->addChild("permission");
				$permission->addAttribute("name", "caninsert");
				// for some reason adding value directly from addChild doesn´t work, this is a workaround
				if($this->userIdentity)
					$permission->{0} = ($this->userIdentity->perm_update == 1 || $this->userIdentity->is_admin == 1) ? "true" : "false";
			}
			else
			{
				$ret = $this->context->gtp->update($post["content"]);
				$code = $xml->addChild("returncode", $ret);
				if($ret%2 != 0)
				{
					$this->context->sync->insertSync("Galaxyplugin", $post["content"], \GLOTR\Sync::COMPRESSION_PLAIN);
				}
			}


		}

		Nette\Diagnostics\Debugger::$bar = FALSE;
		$this->sendResponse(new Nette\Application\Responses\TextResponse($xml->asXML()));

	}

}
