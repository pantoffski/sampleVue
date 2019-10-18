<?php
$app->group('/upload', function () use ($app) {
  $app->POST('/chunk', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $d   = $req->getParsedBody();
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }

    $url = dirname(__FILE__) . "/../../upload/chunk/" . $d['url'];
    // mkdir (recursive) if not exists
    $_ = explode('/', $d['url']);
    array_pop($_);
    $dir = dirname(__FILE__) . "/../../upload/chunk/" . implode($_, '/');
    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }

    // init if first chunk
    if ($d['chunkId'] == 0) {
      delOldFileInChunkFolder();
      // del existing file
      if (file_exists($url)) {
        unlink($url);
      }
    }
    // file is missing while upload
    if ($d['chunkId'] > 0 && !file_exists($url)) {
      return $res->withStatus(500);
    }

    if (file_put_contents(dirname(__FILE__) . "/../../upload/chunk/" . $d['url'], base64_decode($d['chunkData']), FILE_APPEND)) {
      return $res->withStatus(200);
    } else {
      return $res->withStatus(400);
    }
  });
  $app->DELETE('/{id}', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $d   = $req->getParsedBody();
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }
    $r = $db->q("select uId,url from img where id=?",$args['id'])[0];
    if ($r == null || $r['uId'] != $jwt->id) {
      return $res->withStatus(401);
    }
    unlink("/../../upload/" . $r['url']);
    $db->q("delete from img where id=?",$args['id']);
  });
  $app->POST('/move', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $d   = $req->getParsedBody();
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }

    $from = dirname(__FILE__) . "/../../upload/chunk/" . $d['from'];
    $to   = dirname(__FILE__) . "/../../upload/" . $d['to'];
    if (!file_exists($from)) {
      return $res->withStatus(400);
    }
    $_ = explode('/', $to);
    array_pop($_);
    $dir = implode($_, '/');
    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }
    unlink($to);
    if (rename($from, $to)) {
      $db->q("insert into img(uId,url) values(?,?)", $jwt->id, $d['to']);
      return $res->write(json_encode(['id' => $db->id]));
    }
    return $res->withStatus(500);
  });
  function delOldFileInChunkFolder() {
    $tLimitSec = 24 * 60 * 60; // 1 วัน
    $currTime  = time();
    if ($handle = opendir(dirname(__FILE__) . "/../../upload/chunk")) {
      while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
          if ($currTime - filemtime(dirname(__FILE__) . "/../../upload/chunk/" . $entry) > $tLimitSec) {
            unlink(dirname(__FILE__) . "/../../upload/chunk/" . $entry);
          }
        }
      }
      closedir($handle);
    }
  }
});
