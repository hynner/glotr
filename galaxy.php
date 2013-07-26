<?php
$url = "universe";
$method = "POST";
$arr = array(
	"system" => 26,
	"galaxy" => 1,
	"timestamp" => time(), // always send timestamps from SERVER TIMEZONE !!!
	"planets" => array(
		"1" => array(),
		"2" => array(),
		"3" => array(),
		"4" =>  array(
			"name" => "Homewolrd",
			"player" => array(
				"id" => "100171",
				"playername" => "Poltergeist",
				"alliance" => array(),
				"status" => "I"
			),
			"id" => 33622557,
			"moon" => array(),
			"df" => array(),
			"activity" => FALSE
		),
		"5" =>  array(
			"name" => "Nox",
			"player" => array(
				"id" => "100316",
				"playername" => "TartatusGO",
				"alliance" => array(),
				"status" => "a"
			),
			"id" => 33626972,
			"moon" => array(),
			"activity" => FALSE
		),
		"6" =>  array(
			"name" => "Homeworld",
			"player" => array(
				"id" => "100207",
				"playername" => "bennebGO",
				"alliance" => array(),
				"status" => "a"
			),
			"id" => 33623552,
			"moon" => array(),
			"activity" => FALSE
		),
		"7" =>  array(
			"name" => "Galaxytool11",
			"player" => array(
				"id" => "100013",
				"playername" => "eX0du5",
				"status" => "i",
				"alliance" => array(
					"id" => 2,
					"tag" => "GTOOL",
					"name" => "GALAXYTOOL"
				)
			),
			"id" => 33626436,
			"moon" => array(),
			"activity" => FALSE
		),
		"8" =>  array(
			"name" => "Homeworld",
			"player" => array(
				"id" => "100351",
				"playername" => "hynner2",
				"status" => "",
				"alliance" => array()
			),
			"id" => 33627872,
			"moon" => array(
				"id" => "33628829",
				"name" => "Moon",
				"size" => 8485,
				"activity" => FALSE
			),
			"activity" => time()
		),
		"9" =>  array(
			"name" => "Colony",
			"player" => array(
				"id" => "100351",
				"playername" => "hynner2",
				"status" => "",
				"alliance" => array()
			),
			"id" => 33627881,
			"moon" => array(
				"id" => "33628840",
				"name" => "Moon",
				"size" => 8602,
				"activity" => FALSE
			),
			"activity" => FALSE
		),
		"10" =>  array(
			"name" => "Homeworld",
			"player" => array(
				"id" => "100173",
				"playername" => "kokx2",
				"status" => "I",
				"alliance" => array()
			),
			"id" => 33622569,
			"moon" => array(),
			"activity" => FALSE
		),
		"11" =>  array(
			"name" => "Flegethon",
			"player" => array(
				"id" => "100316",
				"playername" => "TartatusGO",
				"status" => "a",
				"alliance" => array()
			),
			"id" => 33626957,
			"moon" => array(),
			"activity" => FALSE
		),
		"12" =>  array(
			"name" => "Homeworld",
			"player" => array(
				"id" => "100314",
				"playername" => "G843SGO",
				"status" => "a",
				"alliance" => array()
			),
			"id" => 33626946,
			"moon" => array(),
			"activity" => FALSE
		),
		"13" => array(),
		"14" => array(),
		"15" => array(),
	)
);