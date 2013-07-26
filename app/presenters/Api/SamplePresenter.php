<?php
namespace ApiModule;

use Drahak\Restful\IResource;

/**
 * SamplePresenter resource
 * @package ResourcesModule
 * @author Drahomír Hanák
 */
class SamplePresenter extends BasePresenter
{
   /**
    * @GET sample[.<type xml|json>]
    */
   public function actionContent($type = 'json')
   {
      /* $this->resource->title = 'REST API';
       $this->resource->subtitle = '';
	   $this->resource->content = array(
		   "aaa" => array(
			   "bbb" => array(
				   "aaa" => 5,
				   "ccc" => 10
			   ) ,
			  "ddd" => 15
		   ),
		   "ssss" => 25
	   );*/
       $this->sendResource($this->typeMap[$type]);
   }

   /**
    * @GET sample/detail[.<type xml|json>]
    */
   public function actionDetail($type = "json")
   {
       $this->resource->message = 'Hello world';
	   $this->sendResource($this->typeMap[$type]);
   }

}