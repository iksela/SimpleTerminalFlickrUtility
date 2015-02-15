<?php
require_once 'STFU.php';

class STFU_ASD {
	private $stfu;

	private $folder;
	private $root;
	private $albums = array();

	public function __construct($folder) {
		$this->stfu = new SimpleTerminalFlickrUtility('Auto Upload Downloader');
		$this->folder = $folder;
		$this->root = $this->stfu->root;
	}

	public function getAutoSyncAlbum() {
		Color::text("Looking for Auto Sync album...\t");
		$sets = $this->stfu->api->photosets_getList();

		$found = false;
		foreach($sets['photoset'] as $set) {
			$album = new Album($set);
			$this->albums[$album->name] = $album;
			if ($album->name == "Auto Sync") {
				Color::ok();
				$found = $album;
			}
		}

		if (!$found) {
			Color::text("FAIL!\n", Color::red);
			exit;
		}
		return $found;
	}

	public function download($photo, $album) {
		$start = microtime(true);
		Color::text("Copying ".$photo['title']." ...\t");

		// filename
		$filename = preg_replace('/[^A-Za-z0-9\-_.+~]/', '', $photo['title']);
		// remove almost all special chars (source @ http://stackoverflow.com/questions/14114411)
		$file = $filename.'.'.$photo['originalformat'];
		
		// prepare folder & fullpath
		$folder = $this->folder . DIRECTORY_SEPARATOR . str_replace('::', DIRECTORY_SEPARATOR, $album);
		if (!is_dir($folder)) {
			echo "(Need to create album $album ...\t";
			if (!mkdir($folder, null, true)) {
				Color::text('FAILED!', Color::red);
				exit;
			}
			Color::text("OK!", Color::green);
			echo ")\t";
		}
		$dest = $folder . DIRECTORY_SEPARATOR . $file;

		// do the copy
		copy($photo['url_o'], $dest);

		// determine speed
		$end = microtime(true);
		$fileSize = filesize($dest);
		$speed = round($fileSize/1024 / ($end - $start));

		Color::ok(false);
		echo "($speed Kb/s)\n";

		return $filename;
	}

	public function moveToAlbum($photo, $newPhotoName, $autoSyncAlbum, $album) {
		echo "Performing flickr operations:\t";
		if (array_key_exists($album, $this->albums)) {
			echo "Add to album\t";
			$this->stfu->api->photosets_addPhoto($this->albums[$album]->id, $photo['id']);
		}
		else {
			echo "Create album $album\t";
			$newAlbum = $this->stfu->api->photosets_create($album, null, $photo['id']);
			$this->albums[$album] = Album::initNew($newAlbum, $album);
		}
		echo "Remove from Auto Sync album\t";
		$this->stfu->api->photosets_removePhoto($autoSyncAlbum->id, $photo['id']);

		echo "Rename photo\t";
		$this->stfu->api->photos_setMeta($photo['id'], $newPhotoName, null);

		Color::text("DONE!\n", Color::green);
	}

	public function exec() {
		$autoSyncAlbum = $this->getAutoSyncAlbum();
		$photos = $autoSyncAlbum->getPhotos($this->stfu);
		foreach ($photos as $photo) {
			// get album from date taken
			$dateTaken = new DateTime($photo['datetaken']);
			$album = $dateTaken->format('Y').'::'.$dateTaken->format('m');

			$newPhotoName = $this->download($photo, $album);
			$this->moveToAlbum($photo, $newPhotoName, $autoSyncAlbum, $album);
		}
	}
}


if (count($argv) < 2) {
	Color::text("Usage: php ".$argv[0]." <folder>\n", Color::red);
	exit;
}

$stfu = new STFU_ASD($argv[1]);
$stfu->exec();

Color::text("\n\nYATA!!\n", Color::blue);
