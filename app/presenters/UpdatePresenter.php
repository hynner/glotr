<?php


use Nette\Application\UI\Form,
	Nette\Security as NS;


/**
 * Sign in/out presenters.
 */
class UpdatePresenter extends BasePresenter
{
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
					try{
						$this->context->authenticator->authenticateByLogonKey($token);
						$check = false;
						//$this->getUser()->getStorage()->setAuthenticated(true);
						//$this->getUser()->setExpiration("+30 minutes");
					}
					catch(NS\AuthenticationException $e)
					{
						echo "User not found";
					}

				}
		}








		if($check && !$this->getUser()->isLoggedIn())
			$this->redirect("Sign:in");




	}
	public function actionUpdateAll()
	{
		if(!$this->context->parameters["enableOgameApi"])
		{
			$response = array("status" => "disabled");
			goto send;
		}
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
			foreach($this->context->highscore->getCategories() as $cat)
				foreach($this->context->highscore->getTypes() as $type)
					if($this->context->highscore->needApiUpdate($cat, $type))
					{
						$this->context->highscore->updateFromApi($cat, $type);
						if($this->context->highscore->needApiUpdate($cat, $type))
							$response = array("status" => "continue", "what" => $this->context->translator->translate("highscores"));
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
					)

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
				$permission->{0} = "true";
			}
			else
			{
				$ret = $this->context->gtp->update($post["content"]);
				$code = $xml->addChild("returncode", $ret);
				/*switch($post["type"]):
				case "galaxyview":
					// 601 - galaxyview updated

					break;
				case "reports":
					// 611 - espionage reports updated
					$code = $xml->addChild("returncode", "611");
					break;
				case "allypage":
					// 631 - allyhistory updated
					$code = $xml->addChild("returncode", "631");
					break;
				case "player_highscore":
					// 622 - stats updated
					$code = $xml->addChild("returncode", "622");

					break;
				default:
					Nette\Diagnostics\Debugger::log($post["type"]);
					break;

				endswitch;*/
			}


		}

		Nette\Diagnostics\Debugger::$bar = FALSE;
		$this->sendResponse(new Nette\Application\Responses\TextResponse($xml->asXML()));

	}

}
