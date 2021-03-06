<?php


class Jdb {

  protected $link;

  public function __construct($db = _DB_NAME, $host = _DB_HOST, $user = _DB_USER, $password = _DB_PASSWD, $socket = _DB_SOCK) {

    $this->link = mysqli_connect($host, $user, $password, $db, 0, $socket) or print("Error " . mysqli_error($this->link));

    if ($this->link->connect_errno > 0) {

      die('DB_ERROR [' . $this->link->connect_error . ']');
    }

    if (!$this->link) {
      var_dump($this->link->error);
      //debug_backtrace ();
      die("DB_ERROR:" . mysqli_error($this->link));
    }

    $this->link->query("SET character_set_client=utf8");
    $this->link->query("SET character_set_connection=utf8");
    $this->link->query("SET character_set_results=utf8");
  }

  public function getResult($sQuery) {


    if (!$result = $this->link->query($sQuery)) {
//      var_dump( debug_backtrace ());
      die('DB_ERROR [' . $this->link->error . ']' . ' STMT :' . $sQuery);
    }

    return $result;
  }

    public function getRows( $sTable, $aKeys, $aLimit = [], $cond = 'AND') {

    $sStmt = 'SELECT * FROM `' . $sTable . '` WHERE ';
    $aCond = ['1'];
    if(count($aKeys)){
      foreach ($aKeys as $key => $value) {
        $aCond[] = '`' . $key . '` = "' . $value . '"';
      }
    }
    $pResult = $this->getResult($sStmt.implode(" $cond ",$aCond));
    $aReturn = [];
    while ($aRow = $pResult->fetch_assoc()) {
      //var_dump($aRow);
      $aReturn[] = $aRow;
    }

    $pResult->free();

    return $aReturn;
  }

  public function getRowsByQuery( $query ) {

  $pResult = $this->getResult($query);
  $aReturn = [];
  while ($aRow = $pResult->fetch_assoc()) {
    //var_dump($aRow);
    $aReturn[] = $aRow;
  }

  $pResult->free();

  return $aReturn;
}


  public function getRow($sQuery) {

    $pResult = $this->getResult($sQuery);

    $aRow = $pResult->fetch_assoc();

    $pResult->free();
    //var_dump($aRow);
    return $aRow;
  }

  /**
   * @param $sTable
   * @param $aKeys
   * @param $aData
   * @return array
   */
  public function updateInsert($sTable, $aKeys, $aData) {
    $aData = array_merge($aData,$aKeys);
    $sCond = '';
    $sStmt = 'SELECT count(*) as count FROM `' . $sTable . '` WHERE ';
    foreach ($aKeys as $key => $value) {
      $sCond .= '`' . $key . '` = "' . $value . '" AND';
    }
    $aRow = $this->getRow(substr($sStmt . $sCond, 0, -3));
    if ($aRow['count'] > 0) {
      //UPDATE
      $sStmt = 'UPDATE `' . $sTable . '` SET ';
      foreach ($aData as $key => $value) {
        $sStmt .= ' `' . $key . '` = "' . addslashes($value) . '",';
      }
      $sStmt = substr($sStmt, 0, -1) . ' WHERE ' . $sCond;
      //var_dump(substr( $sStmt ,0,-3));

      $this->getResult(substr($sStmt, 0, -3));
      //TODO UNSAFE
    } else {
      //INSERT
      foreach ($aData as $key => $value)
        $aData[$key] = "'$aData[$key]'";
      $sStmt = 'INSERT INTO `' . $sTable . '` (' . implode(array_keys($aData), ' ,') . ') VALUES (' . implode(array_values($aData), ' ,') . ') ';

      $this->getResult($sStmt);
    }

    return $aRow;
  }

  /**
   * @param $sTable
   * @param $aData
   * @return bool|mysqli_result
   */
  public function insert($sTable, $aData){
    foreach ($aData as $key => $value)
      $aData[$key] = "'$aData[$key]'";
    $sStmt = 'INSERT INTO `' . $sTable . '` (`' . implode(array_keys($aData), '` ,`') . '`) VALUES (' . implode(array_values($aData), ' ,') . ') ';
    return $this->getResult($sStmt);
  }

  public function update($sTable, $aKeys, $aData) {
    global $bDebug;
    $sCond = '';
    foreach ($aKeys as $key => $value) {
      $sCond .= '`' . $key . '` = "' . $value . '" AND';
    }
    //UPDATE
    $sStmt = 'UPDATE `' . $sTable . '` SET ';
    foreach ($aData as $key => $value) {
      $sStmt .= ' `' . $key . '` = "' . $value . '",';
    }
    $sStmt = substr($sStmt, 0, -1) . ' WHERE ' . $sCond;
    //var_dump(substr( $sStmt ,0,-3));

    $this->getResult(substr($sStmt, 0, -3));
    //TODO UNSAFE
    if ($bDebug)
      return substr($sStmt, 0, -3);
  }

}


if(file_exists(__DIR__.'/config.json')){
  $conf = json_decode(file_get_contents(__DIR__.'/config.json'));
  define( '_DB_NAME',$conf->db_name );
  define( '_DB_HOST',$conf->db_host );
  define( '_DB_USER',$conf->db_user );
  define( '_DB_PASSWD',$conf->db_passwd );
  define( '_DB_SOCK',$conf->db_sock );
}

$objDb = new Jdb(_DB_NAME, _DB_HOST, _DB_USER, _DB_PASSWD, _DB_SOCK);