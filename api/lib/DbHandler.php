<?php
class DbHandler {
  private $_lastInsertId = 0;
  private $_rowCount     = 0;
  private $db;
  private $opts = [
    'dbUsr'                       => '',
    'dbPwd'                       => '',
    'dbName'                      => '',
    'dbHost'                      => 'localhost',
    'PDO_ATTR_ERRMODE'            => PDO::ERRMODE_EXCEPTION,
    'PDO_ATTR_DEFAULT_FETCH_MODE' => PDO::FETCH_ASSOC,
    'PDO_ATTR_EMULATE_PREPARES'   => false,
    'PDO_MYSQL_ATTR_INIT_COMMAND' => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'",
  ];
  public function __construct(array $_opts = []) {
    foreach ($_opts as $key => $val) {
      $this->opts[$key] = $val;
    }
  }
  private function initDb() {
    $opts     = $this->opts;
    $this->db = new PDO(
      "mysql:host=" . $opts['dbHost'] . ";dbname=" . $opts['dbName'] . ";charset=utf8",
      $opts['dbUsr'],
      $opts['dbPwd'],
      [
        PDO::ATTR_ERRMODE            => $opts['PDO_ATTR_ERRMODE'],
        PDO::ATTR_DEFAULT_FETCH_MODE => $opts['PDO_ATTR_DEFAULT_FETCH_MODE'],
        PDO::ATTR_EMULATE_PREPARES   => $opts['PDO_ATTR_EMULATE_PREPARES'],
        PDO::MYSQL_ATTR_INIT_COMMAND => $opts['PDO_MYSQL_ATTR_INIT_COMMAND'],
      ]);
  }
  /*  lazy query function
  + return result set
  - ie. q('select * from foo')
  - ie. q('select * from foo where id=?', $id)
  - ie. q('update foo set a=?,b=? where id=?', $a,$b,$id)
   */
  public function q() {
    $res = null;
    if ($this->db === null) {
      $this->initDb();
    }
    $args = func_get_args();
    if (count($args) == 0 || gettype($args[0]) != 'string') {
      return null;
    }

    if (defined('DB_LOG')) {
      $tt = microtime(true);
    }
    if (count($args) == 1) {
      $res = $this->db->query($args[0])->fetchAll();
    } else {
      $this->st = $this->db->prepare($args[0]);
      $this->st->execute(array_slice($args, 1));
      try {
        $res                 = $this->st->fetchAll();
        $this->_lastInsertId = $this->db->lastInsertId();
        $this->_rowCount     = count($res);

      } catch (PDOException $e) {}
    }
    if (defined('DB_LOG')) {
      $tt = microtime(true) - $tt;
      file_put_contents(DB_LOG, $args[0] . "\n" . $tt . "\n", FILE_APPEND);
    }
    return $res;
  }
  public function __get($name) {
    switch ($name) {
      case 'id':
        return $this->_lastInsertId;
      case 'count':
        return $this->_rowCount;
    }
    return null;
  }
  /* mock query with parameter
  + return string
  - ie. q('update foo set a=?,b=? where id=?', 1,2,3) -> update foo set a=1,b=2 where id=3
   */
  public function mock() {
    $ret  = '';
    $args = func_get_args();
    $q    = explode('?', $args[0]);
    if (count($q) > count($args)) {
      return "to few parameter";
    }

    if (count($q) < count($args)) {
      return "to much parameter";
    }

    for ($i = 0; $i < count($q) - 1; $i++) {
      $ret .= $q[$i];
      $ret .= "'" . $args[$i + 1] . "'";
    }
    $ret .= $q[count($q) - 1];
    return $ret;
  }
}
