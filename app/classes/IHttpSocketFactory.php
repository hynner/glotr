<?php

interface IHttpSocketFactory
{
	/** @return \GLOTR\httpSocket */
	function create($host, $file, $port, $conn_time, $timeout);
}