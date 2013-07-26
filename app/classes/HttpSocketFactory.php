<?php

class HttpSocketFactory  extends \Nette\Object implements IHttpSocketFactory
{
	private $container;
    public function __construct(Nette\DI\Container $container)
    {
        $this->container = $container;
    }

    public function create($host, $file, $port, $conn_time, $timeout)
    {
        return $this->container->createHttpSocket($host, $file, $port, $conn_time, $timeout);
    }
}