<?php
namespace ApiModule;

use Drahak\Restful\IResource;

/**
 * Alliances resource
 */
class AlliancesPresenter extends BasePresenter
{

   /**
    * @POST alliances/<id>
    * @param integer $id
    */
   public function actionUpdateAlliance($id)
   {
	   //$this->glotrApi->updateAlliance($id, $this->input->getData());
	   var_dump($this->input->getData());
	   $this->resource->status = "OK";
   }

}