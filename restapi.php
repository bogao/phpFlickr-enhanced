<?php

require_once("enhapi.php");

$keystr = "5ac04839871f9512847f649ce51738d8";
$secstr = "ddb3741ed625283a";

$dbhost = "localhost";
$dbname = "fr";
$dbuser = "dba";
$dbpass = "iDmpDJKiMGjD6wJT";

$f = new phpFlickr($keystr);
$f->enableCache("db", "mysql://$dbuser:$dbpass@$dbhost/$dbname");

function posnull($toEval, $mEval = 'int'){
	if (is_null($toEval)) {
		return NULL;
	}
	switch ($mEval) {
		case 'int' : return intval($toEval);
		case 'bool' : return boolval($toEval);
		case 'str' : return strval($toEval);
		default: die('You are trying to do an evaluation with an unknown type!');
	}
}

function changeBase($url, $addedPath = NULL, $replacedDomain = NULL){
	if (is_null($addedPath)) {
		return replaceHost($url, $replacedDomain);
	} 
	return replaceHost(addPath($url, $addedPath), $replacedDomain);
}
function changePhotoBase(&$photo, $addedPath = NULL, $replacedDomain = NULL){
	$photo['url'] = changeBase($photo['url'], $addedPath, $replacedDomain);
	if (isset($photo['albums'])) {
		foreach ($photo['albums'] as &$album){
			$album['url'] = changeBase($album['url'], $addedPath, $replacedDomain);
		}
	}
}
function changeAlbumBase(&$album, $addedPath = NULL, $replacedDomain = NULL){
	$album['primary']['url'] = changeBase($album['primary']['url'], $addedPath, $replacedDomain);
	if (isset($album['photos'])) {
		foreach ($album['photos'] as &$photo){
			$photo['url'] = changeBase($photo['url'], $addedPath, $replacedDomain);
		}
	}
	if (isset($album['videos'])) {
		foreach ($album['videos'] as &$video){
			$video['url'] = changeBase($video['url'], $addedPath, $replacedDomain);
		}
	}
	if (isset($album['raw'])) {
		foreach ($album['raw'] as &$raw){
			$raw = changeBase($raw, $addedPath, $replacedDomain);
		}
	}
}

$obj = isset($_GET['obj']) ? $_GET['obj'] : 'photo';

if (($obj != 'photo') && ($obj != 'album')){
	die('You are trying to get something that is neither photo nor album!');
}

if (!isset($_GET['username']) && !isset($_GET['userid']) && !isset($_GET['id'])){
	die('You are neither getting the result by user nor by id!');
} elseif (isset($_GET['id'])) { // ById is overriding ByUser
	if ($obj == 'photo'){
		$res = array('data' => getPhotoById($f, $_GET['id'], $_GET['size'], posnull($_GET['album'], 'bool'), $_GET['primary'], posnull($_GET['exif'], 'bool'), posnull($_GET['interestingness'], 'bool')));
		$res = array_merge($res, array('type' => 'photo'));
	} else { // Getting album
		$res = array('data' => getAlbumById($f, $_GET['id'], $_GET['primary'], posnull($_GET['contents'], 'bool'), posnull($_GET['perpage']), posnull($_GET['page']), $_GET['size']));
		$res = array_merge($res, array('type' => 'album'));
	}
} else {
	if (isset($_GET['username'])) {
		$f->people_findByUsername($_GET['username']);
		$userId = $userInfo['id'];
		echo $userId;
	}
    if (isset($_GET['userid'])) {
        $userId = $_GET['userid'];
    }
	if ($obj == 'photo'){
		$res = array('data' => getPhotosByUser($f, $userId, $_GET['mode'], posnull($_GET['perpage']), posnull($_GET['page']), $_GET['size'], posnull($_GET['album'], 'bool'), $_GET['primary'], posnull($_GET['exif'], 'bool'), posnull($_GET['interestingness'], 'bool')));
		if (trim(strtolower($_GET['mode']) != 'single')) {
			$res = array_merge($res, array('type' => 'photos'));
		} else {
			$res = array_merge($res, array('type' => 'photo'));
		}
	} else { // Getting album
		$res = array('data' => getAlbumsByUser($f, $userId, $_GET['primary'], $_GET['mode'], posnull($_GET['quantity'])));
		if (trim(strtolower($_GET['mode']) != 'single')) {
			$res = array_merge($res, array('type' => 'albums'));
		} else {
			$res = array_merge($res, array('type' => 'album'));
		}
	}
}

if (isset($_GET['base']) || isset($_GET['domain'])) {
	switch ($res['type']) {
		case 'photo':
			changePhotoBase($res['data'], $_GET['base'], $_GET['domain']);
			break;
		case 'photos':
			foreach ($res['data'] as &$aPhoto){
				changePhotoBase($aPhoto, $_GET['base'], $_GET['domain']);
			}
			break;
		case 'album':
			changeAlbumBase($res['data'], $_GET['base'], $_GET['domain']);
			break;
		case 'albums': 
			foreach ($res['data'] as &$anAlbum){
				changeAlbumBase($anAlbum, $_GET['base'], $_GET['domain']);
			}
			break;
	}
}

$respData = json_encode($res['data']);

if (isset($_GET['callback'])){

    header('Content-Type: text/javascript; charset=utf8');
    header('Access-Control-Allow-Origin: https://gao.bo');
    header('Access-Control-Max-Age: 3628800');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

    $callback = $_GET['callback'];
    echo $callback.'('.$respData.');';

}else{
    // normal JSON string
    header('Content-Type: application/json; charset=utf8');

    echo $respData;
}
