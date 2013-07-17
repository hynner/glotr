<?php
namespace ApiModule;

use Drahak\Restful\IResource;

/**
 * messages resource
 */
class MessagesPresenter extends BasePresenter
{

   /**
    * @POST messages
    */
   public function actionMessages()
   {
	   $this->glotrApi->insertMessages($this->input->getData());
	   $this->resource->status = "OK";
   }

}