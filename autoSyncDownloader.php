<?php
require_once 'STFU.php';

class STFU_ASD {
	private $stfu;

	private $folder;
	private $root;

	public function __construct($folder) {
		$this->stfu = new SimpleTerminalFlickrUtility('Auto Upload Downloader');
		$this->folder = $folder;
		$this->root = $this->stfu->root;
	}

	public function getAutoSyncAlbum() {
		Color::text("Looking for Auto Sync album...\t");
		$sets = $this->stfu->api->photosets_getList();

		foreach($sets['photoset'] as $set) {
			$album = new Album($set);
			if ($album->name == "Auto Sync") {
				Color::ok();
				return $album;
			}
		}

		Color::text("FAIL!\n", Color::red);
		exit;
	}

	public function download($photo) {
		$start = microtime(true);
		Color::text("Copying ".$photo['title']." ...\t");

		$dest = $photo['title'].'.'.$photo['originalformat'];
		copy($photo['url_o'], $dest);

		$end = microtime(true);
		$fileSize = filesize($dest);
		$speed = round($fileSize/1024 / ($end - $start));

		Color::ok(false);
		echo "($speed Kb/s)\n";
	}

	public function exec() {
		$album = $this->getAutoSyncAlbum();
		$photos = $album->getPhotos($this->stfu);
		foreach ($photos as $photo) {
			$this->download($photo);
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
