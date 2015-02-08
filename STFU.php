<?php
require_once('phpflickr/phpFlickr.php');

class SimpleTerminalFlickrUtility {

	public $root = null;

	public $api = null;

	public $count = 0;
	public $totalCount = 0;
	public $bytesToUpload = 0;
	public $bytesUploaded = 0;

	private $startTime;

	public function __construct($moduleName=null) {
		Color::text("STFU $moduleName\n\n", Color::blue);
		echo "Setting up flickr connection...\t";
		$cfg = parse_ini_file('config.ini', true);
		$this->root = $cfg['stfu']['root'];
		$this->api = new phpFlickr($cfg['api']['key'], $cfg['api']['secret']);
		$this->api->setToken($cfg['api']['token']);
		Color::ok();
		$this->startTime = microtime(true);
	}

	public function simpleUpload($file) {
		$start = microtime(true);
		echo "Uploading $file ...\t";
		$photoId = $this->api->sync_upload($file);
		$end = microtime(true);

		$fileSize = filesize($file);

		$speed = round($fileSize/1024 / ($end - $start));

		Color::ok(false);
		echo "($speed Kb/s)\t";

		return $photoId;
	}

	public function asyncUpload($file) {
		$start = microtime(true);
		echo "Uploading $file ...\t";
		$photoId = $this->api->async_upload($file);
		$end = microtime(true);

		$fileSize = filesize($file);

		$this->count--;
		$this->bytesUploaded += $fileSize;

		$speed = round($fileSize/1024 / ($end - $start));

		$avgSpeed = round($this->bytesUploaded/1024 / ($end - $this->startTime));

		$seconds = ($this->bytesToUpload - $this->bytesUploaded)/1024 / $avgSpeed;
		// we add the number of files to the seconds, estimating that 1 query to add to an album = 3s
		$seconds += $this->count*3;
		
		$eta = gmdate("H:i:s", $seconds);

		Color::ok(false);
		echo "[Speed: $speed Kb/s\tAverage: $avgSpeed Kb/s\t$this->count files to upload\tETA: $eta]\n";

		return $photoId;
	}

	// Transforms a folder (eg. C:\Photos\2014\11) to an album name (eg. 2014::11), taking a root into account
	public static function folderToAlbum($path, $root) {
		$newPath = substr($path, stripos($path, $root) + strlen($root));
		return str_replace('\\', '::', $newPath);
	}
}

class Color {
	const blue	= 34;
	const green	= 32;
	const red	= 31;
	const none	= 39;

	public static function text($msg, $color=Color::none) {
		echo "\e[".$color."m";
		echo $msg;
		echo "\e[0m";
	}

	public static function ok($newline=true) {
		$nl = ($newline) ? "\n" : "\t";
		Color::text("OK!$nl", Color::green);
	}
}

class PostUploadAction {
	public $name;
	public $albumName;
	public $mustCreateAlbum;

	public function __construct($name, $albumName, $mustCreateAlbum=false) {
		$this->name = $name;
		$this->albumName = $albumName;
		$this->mustCreateAlbum = $mustCreateAlbum;
	}
}

class Album {
	public $id;
	public $name;
	public $nbItems;
	private $photos;

	public function __construct($set) {
		$title = utf8_encode(utf8_decode($set['title']['_content']));
		$this->name = $title;
		$this->id = $set['id'];
		$this->nbItems = intval($set['photos']) + intval($set['videos']);
	}

	public function getPhotos($stfu) {
		//load only if needed
		if (!$this->photos) {
			echo "Loading metadata for album $this->name ...\t";
			$set = $stfu->api->photosets_getPhotos($this->id, "url_o,original_format");
			$this->photos = $set['photoset']['photo'];
			Color::ok();
		}
		return $this->photos;
	}
}
