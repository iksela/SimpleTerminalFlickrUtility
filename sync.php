<?php
require_once 'STFU.php';

class STFU_Check {
	private $stfu;

	private $folder;
	private $root;
	private $albums = array();
	private $orphans = array();

	public function __construct($folder, $root) {
		$this->stfu = new SimpleTerminalFlickrUtility('Folders/Albums Sync');
		$this->folder = $folder;
		$this->root = $root;
	}

	public function getAlbums() {
		Color::text("List all albums...\t");
		$sets = $this->stfu->api->photosets_getList();
		Color::ok();

		foreach($sets['photoset'] as $set) {
			$album = new Album($set);
			$this->albums[$album->name] = $album;
		}

		return $this->albums;
	}

	public function getOrphans() {
		$list = $this->stfu->api->photos_getNotInSet();
		$this->orphans = $list['photos']['photo'];
	}

	private static function photoExists($title, $set) {
		$found = false;
		if (!is_array($set)) {
			Color::text("\nSomething weird happened... Set should be an array!\n", Color::red);
			var_dump($set);
			exit;
		}
		foreach ($set as $key => $photo) {
			if ($photo['title'] == $title)  {
				$found = $key;
				break;
			}
		}
		return $found;
	}

	private function checkFolder($currentAlbum) {
		if (array_key_exists($currentAlbum, $this->albums)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function check() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->folder, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $info) {
			$fullPath	= $info->getRealpath();
			$path		= $info->getPath();

			if (!$info->isDir()) {
				$currentAlbumName = SimpleTerminalFlickrUtility::folderToAlbum($path, $this->root);
				$currentAlbumName = utf8_encode($currentAlbumName);

				// If album doesn't exist, skip it
				if (!$this->checkFolder($currentAlbumName)) continue;

				$album = $this->albums[$currentAlbumName];

				$filename = substr($info->getFilename(), 0, -(strlen($info->getExtension())+1));

				if (self::photoExists($filename, $album->getPhotos($this->stfu)) === false) {
					Color::text("$filename not found!\t", Color::red);
					// check if photo is orphaned
					$orphaned = self::photoExists($filename, $this->orphans);
					if ($orphaned !== false) {
						echo "$filename exists on flickr, but not in an album, adding it to $currentAlbumName ...\t";
						$this->stfu->api->photosets_addPhoto($album->id, $this->orphans[$orphaned]['id']);
						Color::ok();
					}
					else {
						// Not an orphan, only thing left to do is upload it
						$photoID = $this->stfu->simpleUpload($fullPath);
						echo "Add to album ...\t";
						$this->stfu->api->photosets_addPhoto($album->id, $photoID);
						Color::ok();
					}
				}
			}
		}
	}

	public function exec() {
		$this->getAlbums();
		$this->getOrphans();
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

Color::text("\n\nYATA!!\n", Color::blue);
