<?php
namespace Drahak\Restful\Application\Routes;

/**
 * Resource CrudRoute to simple resource creation
 * @package Drahak\Restful\Routes
 * @author Drahomír Hanák
 */
class CrudRoute extends ResourceRoute
{

	/** Presenter action names */
    const ACTION_CREATE = 'create';
	const ACTION_READ = 'default';
	const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    public function __construct($mask, $metadata = '', $flags = IResourceRouter::CRUD)
    {
        parent::__construct($mask, is_string($metadata) ? $metadata . ':default' : $metadata, $flags);
        $this->actionDictionary = array(
            IResourceRouter::POST => self::ACTION_CREATE,
            IResourceRouter::GET => self::ACTION_READ,
            IResourceRouter::PUT => self::ACTION_UPDATE,
            IResourceRouter::DELETE => self::ACTION_DELETE
        );
    }

}