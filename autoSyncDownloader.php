<?php
require_once 'STFU.php';

class STFU_ASD {
	private $stfu;

	private $folder;
	private $root;
	private $albums = array();
	private $dontmove = false;

	public function __construct($folder, $dontmove) {
		$this->stfu = new SimpleTerminalFlickrUtility('Auto Upload Downloader');
		$this->folder = $folder;
		$this->root = $this->stfu->root;
		$this->dontmove = $dontmove;
	}

	public function download($photo, $album) {
		$start = microtime(true);
		Color::text("Copying ".$photo['title']." ...\t");

		// filename
		// remove almost all special chars (source @ http://stackoverflow.com/questions/14114411)
		$filename = preg_replace('/[^A-Za-z0-9\-_.+~]/', '', $photo['title']);

		if ($photo['media'] == 'video') {
			// Special URL building if asset is a video
			$source = "https://www.flickr.com/photos/".$this->stfu->userID."/".$photo['id']."/play/orig/".$photo['originalsecret']."/";
			// If video, assume mp4 extension
			$file = $filename.'.mp4';
		}
		else {
			$source = $photo['url_o'];
			$file = $filename.'.'.$photo['originalformat'];
		}
		
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
		copy($source, $dest);

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
		// create an index of all albums
		Color::text("Listing all albums...\t");
		$sets = $this->stfu->api->photosets_getList();
		Color::ok();
		foreach ($sets['photoset'] as $album) {
			$this->albums[$album['title']['_content']] = new Album($album);
		}

		// Retrieve AutoSync album and begin downloading
		$autoSyncAlbum = $this->stfu->getAutoSyncAlbum();
		$photos = $autoSyncAlbum->getPhotos($this->stfu);
		foreach ($photos as $photo) {
			// get album from date taken
			$dateTaken = new DateTime($photo['datetaken']);
			$album = $dateTaken->format('Y').'::'.$dateTaken->format('m');

			$newPhotoName = $this->download($photo, $album);
			if (!$this->dontmove) $this->moveToAlbum($photo, $newPhotoName, $autoSyncAlbum, $album);
		}
	}
}


if (count($argv) < 2) {
	Color::text("Usage: php ".$argv[0]." <folder> (optional: --dontmove)\n", Color::red);
	exit;
}

$dontmove = false;

if (count($argv) == 3) {
	if ($argv[2] == "--dontmove") $dontmove = true;
}

$stfu = new STFU_ASD($argv[1], $dontmove);
$stfu->exec();

Color::text("\n\nYATA!!\n", Color::blue);
