<?php
namespace ApiModule;

use Drahak\Restful\IResource;

/**
 * Players resource
 */
class PlayersPresenter extends BasePresenter
{

   /**
    * @PATCH players/<id>
    * @param integer $id
    */
   public function actionUpdatePlayer($id)
   {
	   $this->glotrApi->updatePlayer($id, $this->input->getData());
	   $this->resource->status = "OK";
   }

}