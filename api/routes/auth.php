<?php
$app->group('/auth', function () use ($app) {
  $app->POST('/login', function ($req, $res, $args) {
    $db = $req->getAttribute('db');
    $d  = $req->getParsedBody();
    if ($d['pass'] != '555') {
      return $res->withStatus(401)->write('รหัสผ่านไม่ใช่ 555');
    }
    if ($d['user'] == null || trim($d['user']) == '') {
      return $res->withStatus(400)->write('ระบุชื่อ user ด้วย');
    }
    $db->q("insert ignore into user(uName) values(?)", $d['user']);
    $id = $db->q("select id from user where uName=?", $d['user'])[0]['id'];
    if ($id == null) {
      return $res->withStatus(500)->write('server error หว่า');
    }
    $payload['id']    = $id;
    $payload['uName'] = $d['user'];
    return $res->write(json_encode(['token' => genJWT($payload)]));
  });

  $app->GET('/refresh', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $d   = $req->getParsedBody();
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }
    // user โดนลบจากระบบก็ไม่ renew token ให้งี้
    if (null == $db->q("select id from user where id=?", $jwt->id)[0]) {
      return $res->withStatus(401);
    }
    $payload['id']    = $jwt->id;
    $payload['uName'] = $jwt->uName;
    return $res->write(json_encode(['token' => genJWT($payload)]));
  });

});
