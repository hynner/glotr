<?php
$id = 55;
$url = "alliances/$id";
$method = "POST";
$arr = array(
	"timestamp" => time(),
	"alliance" => array(
		"name" => "GLOTR",
		"tag" => "GLOTR",
		"homepage" => NULL,
		"logo" => NULL,
		"open" => true
	),
	"members" => array(
		// id_player_ogame => activity timestamp
		100349 => time(),
		100350 => FALSE,
		123456 => NULL // NULL means that you can´t see online times
	)

);