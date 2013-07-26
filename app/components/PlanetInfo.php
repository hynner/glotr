<?php
namespace GLOTR\Components;
use Nette\Application\UI\Control;
/**
 * Represents the whole planet info
 */
class PlanetInfo extends GLOTRControl
{
	/** @var array */
	protected $userPlayer;
	/** @var \Nette\Localization\ITranslator */
	protected $translator;
	/** @var \GLOTR\Authenticator */
	protected $authenticator;
	/** @var array */
	protected $espKeys;
	/** @var \Nette\Callback */
	protected $filterData;
	/** @var array */
	protected $serverData;
	public function injectTranslator(\Nette\Localization\ITranslator $translator)
	{
		if ($this->translator) {
            throw new Nette\InvalidStateException('Translator has already been set');
        }
        $this->translator = $translator;
	}
	public function injectAuthenticator(\GLOTR\Authenticator $auth)
	{
		if ($this->authenticator) {
            throw new Nette\InvalidStateException('Authenticator has already been set');
        }
        $this->authenticator = $auth;
	}
	public function injectEspionageKeys($keys)
	{
		if ($this->espKeys) {
            throw new Nette\InvalidStateException('Esp keys have already been set');
        }
		$this->espKeys = $keys;
	}
	public function injectUserPlayer($player)
	{
		if ($this->userPlayer) {
            throw new Nette\InvalidStateException('User player has already been set');
        }
		$this->userPlayer = $player;
	}
	public function injectFilterDataCallback(\Nette\Callback $f)
	{
		if ($this->filterData) {
            throw new Nette\InvalidStateException('FilterData callback has already been set');
        }
		$this->filterData = $f;
	}
	public function injectServerData($data)
	{
		if ($this->serverData) {
            throw new Nette\InvalidStateException('Server data has already been set');
        }
		$this->serverData = $data;
	}
	public function render($result, $moon = false)
	{

		$prefix = "planet_";
		if($moon)
			$prefix = "moon_";

		$template = $this->createTemplate();
		$template = $template->setFile(__DIR__ .'/PlanetInfo.latte');
		$template->setTranslator($this->translator);
		$template->perm = $this->authenticator->checkPermissions("perm_planet_info");

		$template->result = $result;
		$template->dateTimeFormat = "j. n. Y H:i:s";
		$tmp = $this->espKeys;

		$template->resources = $this->filterData->invokeArgs(array($result, $tmp[$prefix."resources"], true));
		$template->fleet = $this->filterData->invokeArgs(array($result, $tmp[$prefix."fleet"], false));
		$template->defence = $this->filterData->invokeArgs(array($result, $tmp[$prefix."defence"], false));
		$template->buildings = $this->filterData->invokeArgs(array($result,$tmp[$prefix."buildings"], false));
		$template->researches = $this->filterData->invokeArgs(array($result, $tmp["researches"], true));
		$template->ident = $result['id_planet_ogame'].$moon.  \Nette\Utils\Strings::random(5);
		$template->prefix = $prefix;
		$template->moon = $moon;
		$template->render();
	}
	protected function createComponentPlanetInfoBlock()
	{
		$control =  new PlanetInfoBlock;
		$control->injectTranslator($this->translator);
		return $control;
	}
	protected function createComponentSimulatorLinks()
	{
		$control = new SimulatorLinks;
		$control->injectUserPlayer($this->userPlayer);
		$control->injectTranslator($this->translator);
		$control->injectServerData($this->serverData);
		return $control;
	}
}