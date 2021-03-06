# What is it?
STFU is a simple CLI PHP set of scripts to upload a whole folder tree to flickr, with no restrictions in terms of folder depth.
A folder "C:\Photos\2014\01\My Birthday" will be translated as an album "2014::01::My Birthday".

# Install

## Requirements
* PHP 5.4 with CURL

## Instructions
* `git clone https://github.com/iksela/SimpleTerminalFlickrUtility.git`
* `cd SimpleTerminalFlickrUtility`
* `git clone https://github.com/iksela/phpflickr.git`
* edit `config.ini` to your needs

## How to fill config.ini ?
Go to `phpflickr/auth.php`
Set `$permissions` to `write` or `delete`
Set your `$api_key` and `$api_secret` (you must create an API key from flickr if you don't have one).
Use a local webserver to go to `phpflickr/auth.php`, get redirected to flickr and authorize the app
Use a local webserver to go to `phpflickr/getToken.php` and copy the token to your `config.ini`

# Usage

## Uploader
Use this to upload a local folder/tree to flickr

`php asyncUpload.php <folder>`

## Reorder albums
Use this to order your albums, most recent first

`php orderAlbums.php`

## Sync
Use this if you encountered errors while uploading, or if you added new local content that needs to be pushed to flickr

`php sync.php <folder>`

## AutoSync Downloader
Use this if you use the AutoSync feature on your phone and would like to download the AutoSync'd files in the right folders, then move the files out of the AutoSync album and into their respective albums according to the date they were taken.
Use the dontmove option to leave the photos in the AutoSync folder.

`php autoSyncDownloader.php <folder> (optional: --dontmove)`

## AutoSync Duplicate Finder
Use this if you want to remove duplicate entries from your AutoSync album before using the AutoSync Downloader.

`php duplicate.php (optional: --dry)`