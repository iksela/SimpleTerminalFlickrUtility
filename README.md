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

# Usage

## Uploader
`php asyncUpload.php <folder>`

## Reorder albums
`php orderAlbums.php`

## Sync (in case of errors while uploading)
`php sync.php <folder>`
