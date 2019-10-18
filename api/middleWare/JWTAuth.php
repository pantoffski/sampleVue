<?php

namespace Slim\Middleware;

class JWTAuth {
  protected $jwtSecret;
  protected $acl;
  protected $dbName;
  protected $dbUsr;
  protected $dbPwd;
  public function __construct(array $options = []) {
    $this->jwtSecret = '';
    $this->dbName    = '';
    $this->dbUsr     = '';
    $this->dbPwd     = '';
    $this->acl       = [];
    if (isset($options["secret"])) {
      $this->jwtSecret = $options['secret'];
    }
    if (isset($options["dbName"])) {
      $this->dbName = $options['dbName'];
    }
    if (isset($options["dbUsr"])) {
      $this->dbUsr = $options['dbUsr'];
    }
    if (isset($options["dbPwd"])) {
      $this->dbPwd = $options['dbPwd'];
    }
  }
  public function __invoke($req, $res, $next) {
    require_once __DIR__ . '/../lib/DbHandler.php';
    $db = new \DbHandler([
      'dbUsr'  => $this->dbUsr,
      'dbPwd'  => $this->dbPwd,
      'dbName' => $this->dbName,
    ]);
    $method = $req->getMethod();
    $token  = $this->fetchToken($req);
    $req    = $req->withAttribute('db', $db);
    $req    = $req->withAttribute('jwt', $token);
    $res    = $next($req, $res);
    $db     = null;
    return $res;
  }
  private function fetchToken($req) {
    $headers = $req->getHeader('Authorization');
    $header  = isset($headers[0]) ? explode(" ", $headers[0])[1] : "";
    try {
      return \Firebase\JWT\JWT::decode(
        $header,
        $this->jwtSecret,
        ['HS256']
      );
    } catch (\Exception $exception) {}
    return new \StdClass;
  }
}
