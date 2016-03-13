<?php
$conv = new gp_importer();
$imp = new gp_importer ();
$e_content = "";
$i_content = "";
$extracted = FALSE;
if (rex_post ( 'file-extract', 'boolean' ) and rex_post ( 'fs', 'string' )) {
  $destination = rex_path::media ();
  $zip = new ZipArchive ();
  if ($zip->open ( rex_post ( 'fs', 'string' ) ) === TRUE) {
    $zip->extractTo ( $destination );
    $zip->close ();
    $e_content .= rex_view::info ( $this->i18n ( 'file_extracted' ) . " - " . rex_post ( 'fs', 'string' ) );
    $extracted = TRUE;
    unlink(rex_post ( 'fs', 'string' ));
  } else {
    $e_content .= rex_view::info ( $this->i18n ( 'file_extract_failed' ) );
  }
}
if (rex_post ( 'file-upload', 'boolean' )) {
  $target_dir = rex_path::media ();
  $target_file = $target_dir . basename ( $_FILES ["fileToUpload"] ["name"] );
  $uploadOk = 1;
  $fileType = pathinfo ( $target_file, PATHINFO_EXTENSION );
  if (move_uploaded_file ( $_FILES ["fileToUpload"] ["tmp_name"], $target_file )) {
    $msg = basename ( $_FILES ["fileToUpload"] ["name"] ) . " wurde gespeichert.";
  } else {
    $msg = "Datei wurde nicht gespeichert";
  }
  $i_content .= rex_view::info ( $this->i18n ( 'file_uploaded' ) . " - " . $msg );
}
if (rex_post ( 'file-import', 'boolean' )) {
  $file = rex_post ( 'fs', 'string' );
  if ($file) {
    if($data = $imp->storeFileToDb($file)) {
      $msg = (count($data) - 1) ." Datensätze aus ". basename ( $file ) . " wurde in die Datenbank gespeichert.";
    } else {
      $msg = "Datei wurde nicht gespeichert";
    }
  $e_content .= rex_view::info ( $this->i18n ( 'file_imported' ) . " - " . $msg );
  }
}
$imp = new gp_importer ();
if ($imp->has_input) {
  $files = join ( "<br>", $imp->input_files );
  $pieces = array ();
  $pieces [] = '<div class="rex-form"><form action="' . rex_url::currentBackendPage () . '" method="post">';
  $pieces [] = '<fieldset>';
  $pieces [] = '<table class="table">';
  $pieces [] = '<thead>';
  $pieces [] = '<tr>';
  $pieces [] = '<th> </th>';
  $pieces [] = '<th>Datei</th>';
  $pieces [] = '</tr>';
  $pieces [] = '</thead>';
  $pieces [] = '<tbody>';
  foreach ( $imp->input_files as $file ) {
    $path_parts = pathinfo ( $file );
    $pieces [] = '<tr>';
    $pieces [] = '<td><input type="radio" name="fs" value="' . $file . '"></td>';
    $pieces [] = '<td>' . $path_parts ['filename'] . '.' . $path_parts ['extension'] . '</td>';
    $pieces [] = '</tr>';
  }
  $pieces [] = '</tbody>';
  $pieces [] = '</table>';
  $pieces [] = '</fieldset>';
  $pieces [] = '<fieldset class="rex-form-action">';
  $pieces [] = '<input type="submit" name="file-extract" value="' . $this->i18n ( 'file_extract' ) . '" ' . rex::getAccesskey ( $this->i18n ( 'file_extract' ), 'extract' ) . ' />';
  $pieces [] = '</fieldset>';
  $pieces [] = '</form>';
  ;
  $pieces [] = '</div>';
  
  $i_content .= join ( "\n", $pieces );

}
if (i_content) {
  $fragment = new rex_fragment ();
  $fragment->setVar ( 'body', $i_content, false );
  echo $fragment->parse ( 'core/page/section.php' );
}
if ($imp->has_extracted) {
  $files = join ( "<br>", $imp->extracted_files );
  $tables = join ( "<br>", $imp->dest_tables );
  $pieces = array ();
  $pieces [] = '<div class="rex-form"><form action="' . rex_url::currentBackendPage () . '" method="post">';
  $pieces [] = '<fieldset>';
  $pieces [] = '<table class="table">';
  $pieces [] = '<thead>';
  $pieces [] = '<tr>';
  $pieces [] = '<th> </th>';
  $pieces [] = '<th>Datei</th>';
  $pieces [] = '</tr>';
  $pieces [] = '</thead>';
  $pieces [] = '<tbody>';
  foreach ( $imp->extracted_files as $file ) {
    $path_parts = pathinfo ( $file );
    $pieces [] = '<tr>';
    $pieces [] = '<td><input type="radio" name="fs" value="' . $file . '"></td>';
    $pieces [] = '<td>' . $path_parts ['filename'] . '.' . $path_parts ['extension'] . '</td>';
    $pieces [] = '</tr>';
  }
  $pieces [] = '</tbody>';
  $pieces [] = '</table>';
  $pieces [] = '</fieldset>';
  $pieces [] = '<fieldset class="rex-form-action">';
  $pieces [] = '<input type="submit" name="file-import" value="' . $this->i18n ( 'file_import' ) . '" ' . rex::getAccesskey ( $this->i18n ( 'file_import' ), 'import' ) . ' />';
  $pieces [] = '</fieldset>';
  $pieces [] = '</form>';
  ;
  $pieces [] = '</div>';
  
  $e_content .= join ( "\n", $pieces );
  
  
}
if ($e_content){
  $fragment = new rex_fragment ();
  $fragment->setVar ( 'body', $e_content, false );
  echo $fragment->parse ( 'core/page/section.php' );
}
$content = '<form action="' . rex_url::currentBackendPage () . '" method="post" enctype="multipart/form-data">
    <fieldset>Datei auswählen:</fieldset>
    <fieldset><br><input type="file" name="fileToUpload" id="fileToUpload"></fieldset>
    <fieldset><br><input type="submit" value="Datei zum Server hochladen" name="file-upload"></fieldset>
</form>';

$fragment = new rex_fragment ();
$fragment->setVar ( 'body', $content, false );
echo $fragment->parse ( 'core/page/section.php' );
