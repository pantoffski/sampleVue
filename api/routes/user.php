<?php
$app->group('/user', function () use ($app) {
  $app->GET('', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }
    return $res->write(json_encode($db->q("select * from user order by uName asc")));
  });
  $app->GET('/{id}', function ($req, $res, $args) {
    $db  = $req->getAttribute('db');
    $d   = $req->getParsedBody();
    $jwt = $req->getAttribute('jwt');
    if (!$jwt->id) {
      return $res->withStatus(401);
    }
    $ret         = [];
    $ret['user'] = $db->q("select * from user where id=?", $args['id'])[0];
    $ret['imgs'] = $db->q("select * from img where uId=?", $args['id']);
    return $res->write(json_encode($ret));
  });
});
