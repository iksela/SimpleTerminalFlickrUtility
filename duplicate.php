<?php
require_once 'STFU.php';

class STFU_Duplicate {
	private $stfu;
	private $dry;

	public function __construct($dry) {
		$this->stfu = new SimpleTerminalFlickrUtility('AutoSync Duplicate Finder');
		$this->dry = $dry;
	}

	public function exec() {
		$autoSyncAlbum = $this->stfu->getAutoSyncAlbum();
		$photos = $autoSyncAlbum->getPhotos($this->stfu);

		Color::text("Looking for duplicates ...\n");
		foreach ($photos as $photo) {
			//Color::text("Looking for duplicates of ".$photo['title']." ...\t");
			$search = $this->stfu->api->photos_search(array(
				'user_id'	=> $this->stfu->userID,
				'text'		=> $photo['title']
			));
			//Color::ok();

			if (count($search['photo']) > 1) {
				$compare = array();
				foreach ($search['photo'] as $element) {
					$compare[$element['id']] = $this->stfu->api->photos_getInfo($element['id']);
				}
				
				// set base for comparison the photo we were searching duplicates for and remove it from the comparison array
				$baseCompare = $compare[$photo['id']];
				unset($compare[$photo['id']]);

				$same = false;
				foreach ($compare as $element) {
					if ($baseCompare['photo']['media'] == 'video') {
						// if it's a video, we'll compare the duration
						$same = ($baseCompare['photo']['video']['duration'] == $element['photo']['video']['duration']);
					}
					else {
						// if it's a photo the datetaken exif data should be enough
						$same = ($baseCompare['photo']['dates']['taken'] == $element['photo']['dates']['taken']);
					}
				}

				if ($same) {
					Color::text("Duplicate found for ");
					Color::text($photo['title']."\t", Color::blue);
					if ($this->dry) {
						Color::text("No action taken (dry mode)\n", Color::green);
					}
					else {
						Color::text("Deleting ... ");
						$result = $this->stfu->api->photos_delete($photo['id']);
						($result) ? Color::ok() : Color::text("FAILED\n", Color::red);
					}
				}
			}
		}
	}
}

$dry = false;

if (count($argv) == 2) {
	if ($argv[1] == "--dry") $dry = true;
}

$stfu = new STFU_Duplicate($dry);
$stfu->exec();

Color::text("\n\nYATA!!\n", Color::blue);