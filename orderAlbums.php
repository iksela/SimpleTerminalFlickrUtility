<?php
require_once 'STFU.php';

$stfu = new SimpleTerminalFlickrUtility('Albums Sorter');

Color::text("List all albums...\t");
$sets = $stfu->api->photosets_getList();
Color::ok();

$albums = array();

$error = false;

foreach($sets['photoset'] as $album) {
	$title = $album['title']['_content'];
	$albums[$album['id']] = $title;
	if (substr_count($title, ':')%2 != 0) {
		echo "\nProblem found with ";
		Color::text($title, Color::blue);
		$error = true;
	}
}

if ($error) {
	Color::text("\nFix errors before ordering albums.", Color::red);
	exit();
}

arsort($albums);

$ids = array();
foreach ($albums as $id => $title) $ids[] = $id;

Color::text("Sorting all albums...\t");
$ret = $stfu->api->photosets_orderSets($ids);

if ($ret) Color::ok();
else Color::text("Something went wrong...", Color::red);