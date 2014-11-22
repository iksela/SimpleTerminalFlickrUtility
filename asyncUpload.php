<?php
require_once 'STFU.php';

$stfu = new SimpleTerminalFlickrUtility('Recursive Folder Asynchronous Uploader');

$root = 'Albums\\';

if (count($argv) < 2) {
	echo "Usage: php ".$argv[1]." <folder>\n";
}

$dir = new RecursiveDirectoryIterator($argv[1], FilesystemIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dir);

$actions = array();
$albums = array();

$currentAlbum = null;

// first pass to know what we're up against
echo "Counting files...\t";
foreach ($iterator as $info) {
	$stfu->count++;
	$stfu->bytesToUpload += filesize($info->getPathname());
}
$stfu->totalCount = $stfu->count;
Color::ok(false);
echo "\t$stfu->count files (".round($stfu->bytesToUpload/1024/1024)." Mb)\n\n";

// second pass to do the work!
foreach ($iterator as $info) {
	$path = $info->getPath();
	$file = $info->getPathname();
	$name = $info->getFilename();

	if ($name == 'Thumbs.db') {
		unlink($file);
		echo "Removed useless Thumbs.db\n";
		continue;
	}

	$pwet = substr($path, stripos($path, $root) + strlen($root));
	$albumName = str_replace('\\', '::', $pwet);

	// upload file
	$ticketId = $stfu->asyncUpload($file);

	// add ticket to list with post-upload action
	$mustCreateAlbum = false;
	if ($albumName != $currentAlbum) {
		$mustCreateAlbum = true;
		$currentAlbum = $albumName;
	}
	$actions[$ticketId] = new PostUploadAction($name, $albumName, $mustCreateAlbum);
}

// while there are post-upload actions to do, check tickets
while (count($actions) > 0) {
	echo "Checking tickets (".count($actions)." actions remaining) ...\t";
	$tickets = $stfu->api->photos_upload_checkTickets(array_keys($actions));
	Color::ok();
	foreach ($tickets as $ticket) {
		// ticket complete
		if ($ticket['complete'] == 1) {
			$action = $actions[$ticket['id']];
			// create album if needed, or write photo in album
			if ($action->mustCreateAlbum) {
				echo "Creating album ".$action->albumName." with ".$action->name." ...\t";
				$album = $stfu->api->photosets_create($action->albumName, null, $ticket['photoid']);
				$albums[$action->albumName] = $album['id'];
				Color::ok();
			}
			else {
				echo "Adding ".$action->name." to album ".$action->albumName." ...\t";
				$stfu->api->photosets_addPhoto($albums[$action->albumName], $ticket['photoid']);
				Color::ok();
			}
			// post-upload action complete!
			unset($actions[$ticket['id']]);
		}
	}
	// let's wait a sec before checking for new tickets
	sleep(1);
}

Color::text("\n\nYATA!!\n", Color::blue);