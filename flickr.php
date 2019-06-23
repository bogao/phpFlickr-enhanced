<?php
require_once("path/to/phpFlickr.php"); // Get ALL FILES from https://github.com/dan-coulter/phpflickr instead of phpFlickr.php ONLY

define("USERHOST", "live.staticflickr.com");
// For developers aiming at China mainland marketplace, replace the line above with the follwing three lines:
// define("IPINFODBAPIKEY", "fake-api-key"); // Get yours from https://www.ipinfodb.com/register
// define("USERLOC", ($json = json_decode(@file_get_contents('https://api.ipinfodb.com/v3/ip-country?key=' . IPINFODBAPIKEY . '&ip=' . $_SERVER['REMOTE_ADDR'] . '&format=json'), true)) ? $json['countryCode'] : "US");
// define("USERHOST", USERLOC == "CN" ? "fake.flickrcdn.inchina" : "live.staticflickr.com"); // Try https://www.qiniu.com

function displayTime($tt, $stamp = true, $lang = "zh-cn") {
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
        case "zh-cn":
            return $yy . "年" . $mm . "月" . $dd . "日" . $hh . "时" . $mmn . "分";
        break;
        default:
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
function selectItems($all, $n, $byValue = false) {
    shuffle($all);
    $all = array_slice($all, 0, $n);
    if ($byValue) {
        sort($all);
    } else {
        ksort($all);
    }
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
function getPhotoEXIFById($fObj, $photoId, $lang = "zh-cn") {
    $photoEXIF = array();
    $rawEXIF = $fObj->photos_getExif($photoId);
    switch ($lang) {
        case "zh-cn":
            $names = ["照相机型号", "照相机制造厂商", "镜头规格", "镜头型号", "ISO", "快门", "光圈", "焦距", "闪光灯", "白平衡"];
        break;
        default:
            $names = ["照相机型号", "照相机制造厂商", "镜头规格", "镜头型号", "ISO", "快门", "光圈", "焦距", "闪光灯", "白平衡"];
    }
    $metas = ["model", "manufacturer", "lens specification", "lens model", "iso", "exposure time", "f number", "focal length", "flash", "white balance"];
    if (isset($rawEXIF["camera"])) {
        $itemOrder = 0;
        $contents[$itemOrder] = $rawEXIF["camera"];
        $displays[$itemOrder] = $rawEXIF["camera"];
        array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
    }
    if (isset($rawEXIF["exif"])) {
        foreach ($rawEXIF["exif"] as $rawEXIFitem) {
            if (isset($rawEXIFitem["raw"]["_content"])) {
                $EXIFcontent = $rawEXIFitem["raw"]["_content"];
            };
            if ($rawEXIFitem["tag"]) {
                switch ($rawEXIFitem["tag"]) {
                    case "Make":
                        $itemOrder = 1;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            $displays[$itemOrder] = $EXIFcontent;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "LensSpec":
                        $itemOrder = 2;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            $displays[$itemOrder] = $EXIFcontent;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "LensModel":
                        $itemOrder = 3;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            $displays[$itemOrder] = $EXIFcontent;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "ISO":
                        $itemOrder = 4;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            $displays[$itemOrder] = $EXIFcontent;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "ExposureTime":
                        $itemMeta = "exposure time";
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            switch ($lang) {
                                case "zh-cn":
                                    $exposureTimeUnit = "秒";
                                break;
                                default:
                                    $exposureTimeUnit = "秒";
                            }
                            $displays[$itemOrder] = $EXIFcontent . $exposureTimeUnit;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "FNumber":
                        $itemOrder = 6;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $rawEXIFitem["clean"]["_content"];
                            $displays[$itemOrder] = $rawEXIFitem["clean"]["_content"];
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "FocalLength":
                        $itemOrder = 7;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            switch ($lang) {
                                case "zh-cn":
                                    $focalLengthUnit = "毫米";
                                break;
                                default:
                                    $focalLengthUnit = "毫米";
                            }
                            $displays[$itemOrder] = str_replace(" mm", $focalLengthUnit, $EXIFcontent);
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "Flash":
                        $itemOrder = 8;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            switch ($lang) {
                                case "zh-cn":
                                    $flashDidNotFire = "关闭未闪";
                                break;
                                default:
                                    $flashDidNotFire = "关闭未闪";
                            }
                            switch ($EXIFcontent) {
                                case "Auto, Did not fire":
                                case "Off, Did not fire":
                                case "Off":
                                case "No Flash":
                                    $flashv = $flashDidNotFire;
                                break;
                                default:
                                    $flashv = $EXIFcontent;
                            }
                            $displays[$itemOrder] = $flashv;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                    case "WhiteBalance":
                        $itemOrder = 9;
                        if (noDuplicatedItems($photoEXIF, $itemName)) {
                            $contents[$itemOrder] = $EXIFcontent;
                            switch ($lang) {
                                case "zh-cn":
                                    $autoWhiteBalance = "自动";
                                    $manualWhiteBalance = "手动";
                                break;
                                default:
                                    $autoWhiteBalance = "自动";
                                    $manualWhiteBalance = "手动";
                            }
                            switch ($EXIFcontent) {
                                case "Auto":
                                    $wbv = $autoWhiteBalance;
                                break;
                                case "Manual":
                                    $wbv = $manualWhiteBalance;
                                break;
                                default:
                                    $wbv = $EXIFcontent;
                            }
                            $displays[$itemOrder] = $wbv;
                            array_push($photoEXIF, array("order" => $itemOrder, "item" => $names[$itemOrder], "meta" => $metas[$itemOrder], "content" => $contents[$itemOrder], "display" => $displays[$itemOrder]));
                        }
                    break;
                }
            }
        }
    }
    uasort($photoEXIF, "sortEXIF");
    foreach ($photoEXIF as & $EXIFitem) {
        array_shift($EXIFitem);
    }
    $photoEXIF = array_values($photoEXIF);
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
    $photoInfo = array("id" => $photoId, "title" => $photo["title"]["_content"], "description" => $photo["description"]["_content"], "tags" => $photoTags, "url" => buildImageURL($fObj, $photo, $photoSize), "stamps" => array("taken" => strval(strtotime($photo["dates"]["taken"])), "posted" => $photo["dates"]["posted"], "updated" => $photo["dates"]["lastupdate"]), "dates" => array("taken" => displayTime($photo["dates"]["taken"], false), "posted" => displayTime($photo["dates"]["posted"]), "updated" => displayTime($photo["dates"]["lastupdate"])), "fromToday" => array("taken" => offsetDate($photo["dates"]["taken"], false), "posted" => offsetDate($photo["dates"]["posted"]), "updated" => offsetDate($photo["dates"]["lastupdate"])), "views" => $photo["views"]);
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
    return ($mode == "single") ? $photoInfo[0] : $photoInfo;
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
    $primary["id"] = $album["primary"];
    if ($primarySize == "original") {
        $primaryInfo = getPhotoById($fObj, $album["primary"], $primarySize);
        $primary["url"] = $primaryInfo["url"];
    } else {
        $primary["url"] = buildImageURL($fObj, $album, $primarySize);
    }
    $albumInfo = array("id" => $albumId, "title" => $album["title"]["_content"], "description" => $album["description"]["_content"], "count" => array("photos" => $album["count_photos"], "videos" => $album["count_videos"]), "views" => $album["count_views"], "stamps" => array("created" => $album["date_create"], "updated" => $album["date_update"]), "dates" => array("created" => displayTime($album["date_create"]), "updated" => displayTime($album["date_update"])), "fromToday" => array("created" => offsetDate($album["date_create"]), "updated" => offsetDate($album["date_update"])), "primary" => $primary);
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
    $rawAlbums = $fObj->photosets_getList($userId, NULL, NULL, "original_format");
    if (is_null($primarySize)) {
        $primarySize = "large";
    }
    $total = intval($rawAlbums["total"]);
    if (is_null($mode) && is_null($quantity)) {
        $mode = "all";
    }
    $mode = trim(strtolower($mode));
    if ($mode == "all") {
        $quantity = $total;
        $albumKeys = range(0, $total - 1);
    } else if ($mode == "single") {
        $quantity = 1;
        $albumKeys[0] = rand(0, $total - 1);
    } else {
        if ($quantity > $total) {
            $quantity = $total;
        } else if ($quantity < 1) {
            $quantity = 1;
        }
        $albumKeys = selectItems(range(0, $total - 1), $quantity, true);
    }
    $albums = array();
    foreach ($albumKeys as $albumKey) {
        $rawAlbum = $rawAlbums["photoset"][$albumKey];
        $primaryExtras = array_pop($rawAlbum);
        $album = array_merge($rawAlbum, $primaryExtras);
        $albumInfo = array("id" => $album["id"], "title" => $album["title"]["_content"], "count" => array("photos" => $album["photos"], "videos" => $album["videos"]), "views" => $album["count_views"], "stamps" => array("created" => $album["date_create"], "updated" => $album["date_update"]), "dates" => array("created" => displayTime($album["date_create"]), "updated" => displayTime($album["date_update"])), "fromToday" => array("created" => offsetDate($album["date_create"]), "updated" => offsetDate($album["date_update"])), "primary" => array("id" => $album["primary"], "url" => buildImageURL($fObj, $album, $primarySize)));
        if (trim($albumInfo["description"]) == "") {
            unset($albumInfo["description"]);
        }
        array_push($albums, $albumInfo);
    }
    return ($mode == "single") ? $albums[0] : $albums;
}
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
