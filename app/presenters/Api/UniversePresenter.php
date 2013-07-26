<?php
namespace ApiModule;

use Drahak\Restful\IResource;

/**
 * Universe resource
 */
class UniversePresenter extends BasePresenter
{
	/**
	 * @POST universe
	 */
	public function actionSystem()
	{
		$this->glotrApi->insertSystem($this->input->getData());
		$this->resource->status = "OK";
	}
	/**
	 * @PATCH universe/<id>[/<moon moon>]
	 * @param int $id
	 * @param string|NULL $moon
	 */
	public function actionUpdatePlanet($id, $moon = NULL)
	{
		// I must update it by parts, because some parts might be already update by newer update
		$data = $this->input->getData();
		$prefix = (($moon) ? "moon_" : "");
		$keys = array("resources", "buildings", "fleet", "defence");
		foreach($keys as $k)
		{
			if(!empty($data[$k]))
			{
				$tmp = $this->glotrApi->addPrefixToKeys($prefix, $data[$k]);
				$this->glotrApi->updatePlanet($id, $k, $tmp, $data["timestamp"], ($moon !== NULL), (($moon !== NULL) ? "id_moon_ogame" : "id_planet_ogame"));
			}
		}
		$this->resource->status = "OK";

	}
	/**
	 * @POST fleet_movements
	 */
	public function actionFleetMovements()
	{
		$this->glotrApi->insertFleetMovements($this->input->getData());
		$this->resource->status = "OK";
	}
}