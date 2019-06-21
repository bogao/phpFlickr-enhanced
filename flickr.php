<?php
require_once("path/to/phpFlickr.php"); // Get ALL FILES from https://github.com/dan-coulter/phpflickr instead of phpFlickr.php ONLY

// For developers outside China mainland, simply replace the follwing three lines with
// define("USERHOST", "live.staticflickr.com");
define("IPINFODBAPIKEY", "fake-api-key"); // Get yours from https://www.ipinfodb.com/register
define("USERLOC", ($json = json_decode(@file_get_contents('https://api.ipinfodb.com/v3/ip-country?key=' . IPINFODBAPIKEY . '&ip=' . $_SERVER['REMOTE_ADDR'] . '&format=json'), true)) ? $json['countryCode'] : "US");
define("USERHOST", USERLOC == "CN" ? "flickr.contentdeliver.net" : "live.staticflickr.com");

function timetotxt($tt, $stamp = true, $lang = "chs") {
    // 2016-08-04 16:41:14 => 2016年8月4日16时41分
    if ($stamp) {
        $tt = date('Y-m-d H:i:s', $tt);
    }
    $yy = intval(substr($tt, 0, 4));
    $mm = intval(substr($tt, 5, 2));
    $dd = intval(substr($tt, 8, 2));
    $hh = intval(substr($tt, 11, 2));
    $mmn = intval(substr($tt, 14, 2));
    $ss = intval(substr($tt, 17, 2));
    switch ($lang) {
        case "chs":
            return $yy . "年" . $mm . "月" . $dd . "日" . $hh . "时" . $mmn . "分";
    }
}
function offsetDate($tt, $stamp = true) {
    if ($stamp) {
        $tt = date('Y-m-d', $tt);
    } else {
        $yy = substr($tt, 0, 4);
        $mm = substr($tt, 5, 2);
        $dd = substr($tt, 8, 2);
        $tt = $yy . "-" . $mm . "-" . $dd;
    }
    $oDate = date_diff(date_create($tt), date_create(date('Y-m-d')));
    return $oDate ? intval($oDate->format("%R%a")) : NULL;
}
function joinURL($parsed_url) {
    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass = ($user || $pass) ? "$pass@" : '';
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
}
function replaceHost($oldURL, $newHost) {
    $newURL = parse_url($oldURL);
    $newURL["host"] = $newHost;
    return joinURL($newURL);
}
function noDuplicatedItems($all, $n) {
    foreach ($all as $allnitem) {
        if (array_search($n, $allnitem) == "item") {
            return false;
        }
    }
    return true;
}
function selectItems($all, $n) {
    shuffle($all);
    $all = array_slice($all, 0, $n);
    ksort($all);
    return $all;
}
function sortEXIF($former, $latter) {
    return ($former["order"] < $latter["order"]) ? -1 : (($former["order"] == $latter["order"]) ? 0 : 1);
}
function buildImageURL($fObj, $image, $imageSize) {
    if (array_key_exists("primary", $image)) {
        $image["id"] = $image["primary"];
    }
    return replaceHost($fObj->buildPhotoURL($image, $imageSize), USERHOST);
}
function getTotalByUser($fObj, $userId) {
    $photos = $fObj->people_getPublicPhotos($userId, NULL, NULL, 1);
    return intval($photos["photos"]["total"]);
}
function getPhotoEXIFById($fObj, $photoId) {
    $photoEXIF = array();
    $rawEXIF = $fObj->photos_getExif($photoId);
    if ($rawEXIF["camera"]) {
        array_push($photoEXIF, array("order" => 0, "item" => "照相机型号", "meta" => "model", "content" => $rawEXIF["camera"], "display" => $rawEXIF["camera"]));
        foreach ($rawEXIF["exif"] as $rawEXIFitem) {
            if (isset($rawEXIFitem["raw"]["_content"])) {
                $EXIFcontent = $rawEXIFitem["raw"]["_content"];
            };
            if ($rawEXIFitem["tag"]) {
                switch ($rawEXIFitem["tag"]) {
                    case "Make":
                        $itemOrder = 1;
                        $itemName = "照相机制造厂商";
                        $itemMeta = "manufacturer";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $EXIFcontent));
                        }
                    break;
                    case "LensSpec":
                        $itemOrder = 2;
                        $itemName = "镜头规格";
                        $itemMeta = "lens specification";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $EXIFcontent));
                        }
                    break;
                    case "LensModel":
                        $itemOrder = 3;
                        $itemName = "镜头型号";
                        $itemMeta = "lens model";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $EXIFcontent));
                        }
                    break;
                    case "ISO":
                        $itemOrder = 4;
                        $itemName = "ISO";
                        $itemMeta = "iso";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $EXIFcontent));
                        }
                    break;
                    case "ExposureTime":
                        $itemOrder = 5;
                        $itemName = "快门";
                        $itemMeta = "exposure time";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => ($EXIFcontent . "秒")));
                        }
                    break;
                    case "FNumber":
                        $itemOrder = 6;
                        $itemName = "光圈";
                        $itemMeta = "f number";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $rawEXIFitem["clean"]["_content"], "display" => $rawEXIFitem["clean"]["_content"]));
                        }
                    break;
                    case "FocalLength":
                        $itemOrder = 7;
                        $itemName = "焦距";
                        $itemMeta = "focal length";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => str_replace(" mm", "毫米", $EXIFcontent)));
                        }
                    break;
                    case "Flash":
                        $itemOrder = 8;
                        $itemName = "闪光灯";
                        $itemMeta = "flash";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            switch ($EXIFcontent) {
                                case "Auto, Did not fire":
                                case "Off, Did not fire":
                                case "Off":
                                case "No Flash":
                                    $flashv = "关闭未闪";
                                break;
                                default:
                                    $flashv = $EXIFcontent;
                            }
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $flashv));
                        }
                    break;
                    case "WhiteBalance":
                        $itemOrder = 9;
                        $itemName = "白平衡";
                        $itemMeta = "white balance";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            switch ($EXIFcontent) {
                                case "Auto":
                                    $wbv = "自动";
                                break;
                                case "Manual":
                                    $wbv = "手动";
                                break;
                                default:
                                    $wbv = $EXIFcontent;
                            }
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $itemName, "meta" => $itemMeta, "content" => $EXIFcontent, "display" => $wbv));
                        }
                    break;
                }
            }
        }
        uasort($photoEXIF, "sortEXIF");
        foreach ($photoEXIF as & $EXIFitem) {
            array_shift($EXIFitem);
        }
        $photoEXIF = array_values($photoEXIF);
    }
    return $photoEXIF;
}
function getPhotoById($fObj, $photoId, $photoSize = NULL, $inAlbums = false, $primarySize = NULL, $withEXIF = false, $interestingnessEvaluation = false) {
    if (is_null($photoSize)) {
        $photoSize = "small_320";
    }
    if (is_null($primarySize)) {
        $primarySize = "square";
    }
    $photos = $fObj->photos_getInfo($photoId);
    $photo = $photos["photo"];
    $photoTags = array();
    if (count($photo["tags"]["tag"]) > 0) {
        foreach ($photo["tags"]["tag"] as $photoTag) {
            array_push($photoTags, $photoTag["raw"]);
        }
    }
    $photoInfo = array("id" => $photoId, "title" => $photo["title"]["_content"], "description" => $photo["description"]["_content"], "tags" => $photoTags, "url" => buildImageURL($fObj, $photo, $photoSize), "stamps" => array("taken" => strval(strtotime($photo["dates"]["taken"])), "posted" => $photo["dates"]["posted"], "updated" => $photo["dates"]["lastupdate"]), "dates" => array("taken" => timetotxt($photo["dates"]["taken"], false), "posted" => timetotxt($photo["dates"]["posted"]), "updated" => timetotxt($photo["dates"]["lastupdate"])), "fromToday" => array("taken" => offsetDate($photo["dates"]["taken"], false), "posted" => offsetDate($photo["dates"]["posted"]), "updated" => offsetDate($photo["dates"]["lastupdate"])), "views" => $photo["views"]);
    if (trim($photoInfo["description"]) == "") {
        unset($photoInfo["description"]);
    }
    if (empty($photoTags)) {
        unset($photoInfo["tags"]);
    }
    if ($inAlbums) {
        $sarr = array();
        $albums = $fObj->photos_getAllContexts($photoId);
        if (count($albums["set"]) > 0) {
            foreach ($albums["set"] as $spset) {
                $pInfo = getPhotoById($fObj, $spset["primary"], $primarySize);
                array_push($sarr, array("id" => $spset["id"], "title" => $spset["title"], "primary" => $spset["primary"], "url" => $pInfo["url"]));
            }
        }
        $photoInfo["albums"] = $sarr;
    }
    if ($withEXIF) {
        $photoInfo["exif"] = getPhotoEXIFById($fObj, $photoId);
    }
    if ($interestingnessEvaluation) {
        $photoFavorites = $fObj->photos_getFavorites($photoId);
        $photoInfo["interestingness"] = array("hasDescription" => array_key_exists("description", $photoInfo), "hasComments" => (intval($photo["comments"]["_content"]) != 0), "hasFavorites" => (intval($photoFavorites["total"]) != 0), "hasPeople" => (intval($photo["people"]["haspeople"]) != 0), "hasNotes" => !empty($photo["notes"]["note"]));
        $photoInfo["interestingness"]["overall"] = $photoInfo["interestingness"]["hasDescription"] || $photoInfo["interestingness"]["hasComments"] || $photoInfo["interestingness"]["hasFavorites"] || $photoInfo["interestingness"]["hasFavorites"] || $photoInfo["interestingness"]["hasFavorites"];
    }
    return $photoInfo;
}
function getPhotosByUser($fObj, $userId, $mode = NULL, $perPage = NULL, $pageOrder = NULL, $photoSize = NULL, $inAlbums = false, $primarySize = NULL, $withEXIF = false, $interestingnessEvaluation = false) {
    if (!is_null($mode)) {
        $mode = trim(strtolower($mode));
    }
    if (is_null($photoSize)) {
        $photoSize = "small_320";
    }
    if (is_null($primarySize)) {
        $primarySize = "square";
    }
    $total = getTotalByUser($fObj, $userId);
    if ($mode == "single") {
        $perPage = 1;
        if (is_null($pageOrder)) {
            $pageOrder = rand(1, $total);
        } else {
            if ($pageOrder < 1) {
                $pageOrder = 1;
            }
            if ($pageOrder > $total) {
                $pageOrder = $total;
            }
        }
    } else {
        if (is_null($perPage) || ($perPage > 100)) {
            $perPage = 100;
        } else if ($perPage < 1) {
            $perPage = 1;
        }
        $pageCount = ($total % $perPage == 0) ? ($total / $perPage) : (floor($total / $perPage) + 1);
        if (is_null($pageOrder)) {
            $pageOrder = rand(1, $pageCount);
        } else {
            if ($pageOrder < 1) {
                $pageOrder = 1;
            }
            if ($pageOrder > $pageCount) {
                $pageOrder = $pageCount;
            }
        }
    }
    $photos = $fObj->people_getPublicPhotos($userId, NULL, NULL, $perPage, $pageOrder);
    $photoInfo = array();
    $i = 0;
    foreach ($photos["photos"]["photo"] as $photo) {
        $currentPhotoInfo = getPhotoById($fObj, $photo["id"], $photoSize, $inAlbums, $primarySize, $withEXIF, $interestingnessEvaluation);
        $currentPhotoInfo["total"] = $total;
        $currentPhotoInfo["current"] = $perPage * ($pageOrder - 1) + (++$i);
        array_push($photoInfo, $currentPhotoInfo);
    }
    if ($mode == "single") {
        return $photoInfo[0];
    }
    return $photoInfo;
}
function getAlbumById($fObj, $albumId, $primarySize = NULL, $withContents = false, $perPage = NULL, $pageOrder = NULL, $photoSize = NULL) {
    $album = $fObj->photosets_getInfo($albumId);
    if (is_null($primarySize)) {
        $primarySize = "large";
    }
    if (is_null($photoSize)) {
        $photoSize = "square";
    }
    $primarySize = trim(strtolower($primarySize));
    $photoSize = trim(strtolower($photoSize));
    if ($primarySize == "original") {
        $primaryInfo = getPhotoById($fObj, $album["primary"], $primarySize);
        $primary = $primaryInfo["url"];
    } else {
        $primary = buildImageURL($fObj, $album, $primarySize);
    }
    $albumInfo = array("id" => $albumId, "title" => $album["title"]["_content"], "description" => $album["description"]["_content"], "count" => array("photos" => $album["count_photos"], "videos" => $album["count_videos"]), "views" => $album["count_views"], "stamps" => array("created" => $album["date_create"], "updated" => $album["date_update"]), "dates" => array("created" => timetotxt($album["date_create"]), "updated" => timetotxt($album["date_update"])), "fromToday" => array("created" => offsetDate($album["date_create"]), "updated" => offsetDate($album["date_update"])), "primary" => $primary);
    if (trim($albumInfo["description"]) == "") {
        unset($albumInfo["description"]);
    }
    if ($withContents) {
        $contents = $fObj->photosets_getPhotos($albumId, "media,original_format", NULL, $perPage, $pageOrder);
        $parr = array();
        $varr = array();
        $raw = array();
        $position = 0;
        foreach ($contents["photoset"]["photo"] as $content) {
            if (strtolower($content["media"]) == "photo") {
                $currentPhoto = array("id" => $content["id"], "title" => $content["title"], "url" => buildImageURL($fObj, $content, $photoSize), "total" => count($contents["photoset"]["photo"]), "current" => $perPage * ($pageOrder - 1) + (++$position));
                array_push($parr, $currentPhoto);
                array_push($raw, $currentPhoto["url"]);
            } else if (strtolower($content["media"]) == "video") {
                $currentVideo = array("id" => $content["id"], "title" => $content["title"], "url" => buildImageURL($fObj, $content, $photoSize), "total" => count($contents["photoset"]["photo"]), "current" => $perPage * ($pageOrder - 1) + (++$position));
                array_push($varr, $currentVideo);
                array_push($raw, $currentVideo["url"]);
            }
        }
        if (!empty($parr)) {
            $albumInfo["photos"] = $parr;
        }
        if (!empty($varr)) {
            $albumInfo["videos"] = $varr;
        }
        if (!empty($raw)) {
            $albumInfo["raw"] = $raw;
        }
    }
    return $albumInfo;
}
function getAlbumsByUser($fObj, $userId, $primarySize = NULL, $mode = NULL, $quantity = NULL) {
    $rawalbums = $fObj->photosets_getList($userId, NULL, NULL, "original_format");
    if (is_null($primarySize)) {
        $primarySize = "large";
    }
    $total = intval($rawalbums["total"]);
    if (is_null($mode) && is_null($quantity)) {
        $mode = "all";
    }
    $mode = trim(strtolower($mode));
    if ($mode == "all") {
        $quantity = $total;
    } else if ($mode == "single") {
        $quantity = 1;
    } else {
        if ($quantity > $total) {
            $quantity = $total;
        } else if ($quantity < 1) {
            $quantity = 1;
        }
    }
    $albums = array();
    foreach ($rawalbums["photoset"] as $rawalbum) {
        $primaryExtras = array_pop($rawalbum);
        $album = array_merge($rawalbum, $primaryExtras);
        $albumInfo = array("id" => $album["id"], "title" => $album["title"]["_content"], "count" => array("photos" => $album["photos"], "videos" => $album["videos"]), "views" => $album["count_views"], "stamps" => array("created" => $album["date_create"], "updated" => $album["date_update"]), "dates" => array("created" => timetotxt($album["date_create"]), "updated" => timetotxt($album["date_update"])), "fromToday" => array("created" => offsetDate($album["date_create"]), "updated" => offsetDate($album["date_update"])), "primary" => buildImageURL($fObj, $album, $primarySize));
        if (trim($albumInfo["description"]) == "") {
            unset($albumInfo["description"]);
        }
        array_push($albums, $albumInfo);
    }
    if ($mode != "all") {
        $albums = selectItems($albums, $quantity);
    }
    return ($mode == "single") ? $albums[0] : $albums;
}
?>
// API code ends here.
// Client code follows.

$keystr = "fake-api-key"; // Get yours from https://www.flickr.com/services/api/keys/
$f = new phpFlickr($keystr); // Do include correct phpFlickr.php and its dependencies.

// Database used for caching is optional. Only include the follwing code provided that you do have a database for caching.
// $dbhost = "hostname"; // Usually localhost
// $dbname = "dbname";
// $dbuser = "dbuser";
// $dbpass = "dbpass";
// $f->enableCache("db", "mysql://$dbuser:$dbpass@$dbhost/$dbname");
