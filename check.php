<?php
require_once 'STFU.php';

class STFU_Check {
	private $stfu;

	private $folder;
	private $root;
	private $albums = array();

	public function __construct($folder, $root) {
		$this->stfu = new SimpleTerminalFlickrUtility('Albums Checker');
		$this->folder = $folder;
		$this->root = $root;
	}

	public function getAlbums() {
		Color::text("List all albums...\t");
		$sets = $this->stfu->api->photosets_getList();
		Color::ok();

		foreach($sets['photoset'] as $set) {
			/*
			var_dump($album);exit;
			$title = $album['title']['_content'];
			$title = iconv('UTF-8', 'ASCII//IGNORE', $title);
			$this->albums[$album['id']] = $title;
			*/
			$album = new Album($set);
			$this->albums[$album->name] = $album;
		}

		return $this->albums;
	}

	private function checkFolder($currentAlbum, $items) {
		// search album
		echo "\tExists?\t";
		if (array_key_exists($currentAlbum, $this->albums)) {
			Color::ok(false);

			echo "\tNumber of elements?\t";
			if ($this->albums[$currentAlbum]->nbItems == $items) {
				Color::ok();
			}
			else {
				Color::text("Folder has $items items, album has ".$this->albums[$currentAlbum]->nbItems, Color::red);
			}
		}
		else {
			Color::text("Fail", Color::red);
		}
	}

	public function check() {
		// first folder won't be iterated over, let's init it
		$currentAlbum = SimpleTerminalFlickrUtility::folderToAlbum(realpath($this->folder), $this->root);
		//var_dump($currentAlbum);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		$items = 0;
		foreach ($iterator as $info) {
			$path = $info->getRealpath();

			if ($info->getFilename() == 'Thumbs.db') {
				unlink($path);
				echo "\nRemoved useless Thumbs.db\n";
				continue;
			}

			// new folder reached
			if ($info->isDir()) {
				echo "\nChecking $currentAlbum ...";
				// has items?
				if ($items > 0) {
					// search album
					/*
					echo "\tExists?\t";
					if (array_key_exists($currentAlbum, $this->albums)) {
						Color::ok(false);

						echo "\tNumber of elements?\t";
						if ($this->albums[$currentAlbum]->nbItems == $items) {
							Color::ok();
						}
						else {
							Color::text("Folder has $items items, album has ".$this->albums[$currentAlbum]->nbItems, Color::red);
						}
					}
					else {
						Color::text("Fail", Color::red);
					}*/
					$this->checkFolder($currentAlbum, $items);

					
				}
				else {
					Color::text("\tEmpty", Color::green);
				}
				// switch to new album		
					$currentAlbum = SimpleTerminalFlickrUtility::folderToAlbum($path, $this->root);
					$currentAlbum = iconv('UTF-8', 'ASCII//IGNORE', $currentAlbum);
					$items = 0;
			}
			else {
				$items++;
			}

			/*
			$albumName = SimpleTerminalFlickrUtility::folderToAlbum($path, $this->root);
			$albumName = iconv('UTF-8', 'ASCII//IGNORE', $albumName);
			if ($albumName != $currentAlbum) {
				$items = 1;
				$currentAlbum = $albumName;
				echo "$albumName\n";

				$found = array_key_exists($albumName, $this->albums);
				if ($found) {
					Color::ok();
				}
				else {
					Color::text("Not found\n", Color::red);
				}
			}
			else {
				$items++;
			}*/
		}
		echo "\nChecking $currentAlbum ...";
		$this->checkFolder($currentAlbum, $items);
	}

	public function exec() {
		$this->getAlbums();
		$this->check();
	}
}


$root = 'Albums\\';

if (count($argv) < 2) {
	Color::text("Usage: php ".$argv[0]." <folder>\n", Color::red);
	exit;
}

$stfu = new STFU_Check($argv[1], $root);
$stfu->exec();