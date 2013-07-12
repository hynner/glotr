<?php
namespace ApiModule;

use Nette\Application\UI\Form,
	Nette\Security as NS;


class UpdatePresenter extends BasePresenter
{
   /**
    * @GET update/all[.<type xml|json>]
    */
   public function actionAll($type = "json")
   {
		$this->resource->status = "ok";
		try
		{
			if($this->glotrApi->needUpdate("players"))
			{
				$this->glotrApi->updateFromOgameApi("players");
				$this->resource->status = "continue";
				return;
			}
			if($this->glotrApi->needUpdate("alliances"))
			{
				$this->glotrApi->updateFromOgameApi("alliances");
				$this->resource->status = "continue";
				return;
			}

			if($this->glotrApi->needUpdate("universe"))
			{
				$this->glotrApi->updateFromOgameApi("universe");
				$this->resource->status = "continue";
				return;
			}
			if($this->glotrApi->needUpdate("highscore"))
			{
				$this->glotrApi->updateFromOgameApi("highscore");
				$this->resource->status = "continue";
				return;
			}
			if($this->glotrApi->needUpdate("server"))
			{
				$this->glotrApi->updateFromOgameApi("server");
				$this->resource->status = "continue";
				return;
			}
			if($this->glotrApi->needUpdate("sync"))
			{
				try{
				$this->glotrApi->syncUpdate();
				}
				catch(\Nette\Application\ApplicationException $e)
				{
					$this->resource->status = "sync-failed";
					$this->resource->message = $e->getMessage();
					return;
				}
				if($this->glotrApi->needUpdate("sync"))
				{
					$this->resource->status = "continue";
					$this->resource->sync = 1;
				}
				else
					$this->resource->status = "continue";
				return;
			}

		}
		catch(Nette\Application\ApplicationException $e)
		{}
	}
	/**
	 * @GET verify
	 */
	public function actionVerify()
	{
		$data = $this->input->getData();

		if($data["uni"] == str_replace("http://", "", $this->parameters["server"]))
		{
			$this->resource->status = "OK";
		}
		else
		{
			$this->resource->status = "ERROR";
			$this->resource->message = "Wrong universe";
		}

	}
	/**
	 * @POST update/galaxyplugin.php
	 */
	public function actionGalaxyplugin()
	{
		var_dump($this->input->getData());
		$post = $this->getHttpRequest()->getPost();
		$xml = new \SimpleXMLElement("<?"."xml version=\"1.0\" encoding=\"UTF-8\"?>\n <galaxytool universe=\"".str_replace("http://", "", $this->parameters["server"])."\">\n</galaxytool>");
		$version = $xml->addChild("version");
		$version->addAttribute("major", 4);
		$version->addAttribute("minor", 9);
		$version->addAttribute("revision", 2);;
		$version->addAttribute("glotr_version", $this->parameters["version"]);
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

		\Nette\Diagnostics\Debugger::$bar = FALSE;
		$this->sendResponse(new \Nette\Application\Responses\TextResponse($xml->asXML()));

	}

}
