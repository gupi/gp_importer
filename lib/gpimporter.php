<?php

class gp_importer {
  var $dest_tables;
  var $dest_structures;
  var $input_files;
  var $extracted_files;
  var $asset_path;
  var $media_path;
  var $has_extracted;
  var $has_input;
  var $db;

  function __construct() {
    $this->db = rex_sql::factory ();
    $this->asset_path = rex_path::assets ();
    $this->media_path = rex_path::media ();
    $this->loadInputFiles ();
    $this->loadExtractedFiles ();
  }

  function loadInputFiles() {
    $this->input_files = glob ( $this->media_path . 'r4_*.zip' );
    $this->has_input = count ( $this->input_files );
  }

  function loadExtractedFiles() {
    $this->extracted_files = glob ( $this->media_path . 'r4_*.csv' );
    $this->has_extracted = count ( $this->extracted_files );
  }

  function loadDestinationTables() {
    $this->dest_tables = array ();
    foreach ( $this->input_files as $file ) {
      $f = str_replace ( $this->media_path, "", $file );
      $this->dest_tables [] = str_replace ( "r4", "rex", substr ( $f, 0, strlen ( $f ) - 19 ) );
    }
  }

  function storeFileToDb($file) {
    $search = array (
      "r4_",
      ".csv" 
    );
    $replace = array (
      "rex_",
      "" 
    );
    $dest = str_replace ( $search, $replace, basename ( $file ) );
    $this->loadDestTableStructure ( $dest );
    $data = array ();
    if ($fp = fopen ( $file, "r" )) {
      $this->db->setQuery("TRUNCATE `".$dest."`");
      $head = fgetcsv ( $fp, 0, ";", "'", '\\' );
      while ( $d = fgetcsv ( $fp, 0, ";", "'" ) ) {
        $rec = array ();
        foreach ( $d as $k => $v ) {
          $rec [$head [$k]] = str_replace ( '<§**§>', "\n", array (
            $this->dest_structures [$dest] [$head [$k]] ['Type'],
            $v 
          ) );
        }
        $data [] = $rec;
        $this->insertDbRecord($dest, $rec);
      }
      unlink($file);
      switch($dest) {
        case "rex_module":
          $conv = new gp_converter();
          $conv->convertModules();
          break;
        case "rex_template":
          $conv = new gp_converter();
          $conv->convertTemplates();
          break;
      }
      rex_delete_cache();
      return $data ;
    }
    return FALSE ;
  }

  function insertDbRecord($table, $rec) {
    $sql = "INSERT INTO `" . $table . "` SET ";
    $sql .= $this->setValues ( $rec );
    $this->db->setQuery($sql);
    return;
  }

  function setValues($rec) {
    $set = "";
    $glue = "";
    foreach ( $rec as $key => $field ) {
      if ($field [0]) {
        switch (substr ( $field [0], 0, 3 )) {
          case "tex" :
          case "var" :
          case "dat" :
            $set .= $glue.$key."='".$field[1]."'";
            break;
          case "int" :
          case "flo" :
          case "tin" :
            $set .= $glue.$key."=".$field[1];
            break;
        }
        $glue = ", ";
      }
    }
    return $set;
  }

  function loadDestTableStructure($table) {
    $struc = $this->db->getArray ( "SHOW COLUMNS FROM `" . $table . "`;" );
    foreach ( $struc as $v ) {
      $this->dest_structures [$table] [$v ['Field']] = $v;
    }
    return;
  }
}