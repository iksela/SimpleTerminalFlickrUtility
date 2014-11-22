<?php
require_once('phpflickr/phpFlickr.php');

class SimpleTerminalFlickrUtility {
	private $key	= '';
	private $secret	= '';
	private $token	= '';

	public $api = null;

	public $count = 0;
	public $totalCount = 0;
	public $bytesToUpload = 0;
	public $bytesUploaded = 0;

	private $startTime;

	public function __construct($moduleName=null) {
		Color::text("STFU $moduleName\n\n", Color::blue);
		echo "Connection to flickr...\t";
		$this->api = new phpFlickr($this->key, $this->secret);
		$this->api->setToken($this->token);
		Color::ok();
		$this->startTime = microtime(true);
	}
/*
	public function upload($file) {
		$start = microtime(true);
		echo "Uploading $file ...\t";
		$photoId = $this->api->sync_upload($file);
		$end = microtime(true);

		$fileSize = filesize($file);

		$this->count--;
		$this->remainingBytes -= $fileSize;

		$speed = round($fileSize/1024 / ($end - $start));

		$minutes = round($this->remainingBytes/1024)

		Color::ok(false);
		echo "($speed Kb/s)\t($this->count remaining)\tETA ";

		return $photoId;
	}*/

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
		// we add the number of files to the seconds, estimating that 1 query to add to an album = 2s
		$seconds += $this->totalCount*2;
		
		$eta = gmdate("H:i:s", $seconds);

		Color::ok(false);
		echo "[Speed: $speed Kb/s\tAverage: $avgSpeed Kb/s\t$this->count files to upload\tETA: $eta]\n";

		return $photoId;
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