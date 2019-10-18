<?php
function genJWT($payload) {
  if (!isset($payload['iat'])) {
    $payload['iat'] = (new DateTime())->getTimeStamp();
  }
  if (!isset($payload['exp'])) {
    $payload['exp'] = (new DateTime(JWT_expire))->getTimeStamp();
  }
  return \Firebase\JWT\JWT::encode($payload, JWT_secret, "HS256");
}
// example:: (JWT_TOKEN,a,[b,c],d) => a || (b && c) || d in JWT_TOKEN->scope

function hasScope() {
  $args = func_get_args();
  if (count($args) < 2) {
    return false;
  }
  $jwt = $args[0];
  if (!$jwt) {
    return;
  }

  if (!property_exists($jwt, 'scope')) {
    return false;
  }

  if (count($args) == 2) {
    if (is_array($args[1])) {
      $ret = true;
      foreach ($args[1] as $v) {
        $ret &= in_array($v, $jwt->scope);
      }
      return $ret ? true : false;
    } else {
      return in_array($args[1], $jwt->scope);
    }
  } else {
    for ($i = 1; $i < count($args); $i++) {
      if (in_array($args[$i], $jwt->scope)) {
        return true;
      }
    }
    return false;
  }
}



function genRandom($length = 8) {
  $password = "";
  $possible = "2346789bcdfghjkmnpqrsvwxyzABCDEFGHJKLMNPQRTUVWXYZ";
  $i        = 0;
  while ($i < $length) {
    $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
    $password .= $char;
    $i++;
  }
  return $password;
}
function numPart($inp) {
  return preg_replace("/[^0-9]/", "", $inp);
}
function trace($t) {
  echo "$t<br/>\r\n";
}
function thaiDate($d) {
  $tMonth = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
  $foo    = explode('-', $d);
  return ($foo[2] * 1) . ' ' . $tMonth[$foo[1] * 1] . ' ' . ($foo[0] + 543);
}
