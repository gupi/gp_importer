<?php

class gp_converter {
  
  var $pattern;
  var $mapping;
  var $db;

  function __construct() {
    $this->db = rex_sql::factory ();
    $this->setPattern ();
    $this->setMapping ();
  }

  function setPattern() {
    $this->pattern = array ();
    $this->pattern ['dollar_rex'] = '|\$REX\s*\[[\'\"](.*?)[\'\"]\]|';
    $this->pattern ['rex'] = '(REX_\w*\[(\d{1,2})\])';
    $this->pattern ['value'] = '|[^_](VALUE)|';
    
    $this->pattern ['ooarticle'] = '|OOArticle::(.*?)\(|';
    $this->pattern ['oocategory'] = '|OOCategory::(.*?)\(|';
    $this->pattern ['oomedia'] = '|OOMedia::(.*?)\(|';
    $this->pattern ['oomediacategory'] = '|OOMediaCategory::(.*?)\(|';
    $this->pattern ['ooaddon'] = '|OOAddon::(.*?)\s*\(\s*[\'\"](.*?)[\'\"]\s*\)|';
    
    $this->pattern ['OOPlugin'] = '|OOPlugin::(.*?)\(|';
    $this->pattern ['OOArticleSlice'] = '|OOArticleSlice::(.*?)\(|';
    $this->pattern ['php'] = '|<\?php(.*?)\?>|';
    $this->pattern ['temp'] = '|REX_TEMPLATE\[(\d*)\]|';
  }

  function setMapping() {
    $this->mapping = array ();
    /* OOAddon */
    $this->mapping ['OOAddon'] ["isAvailable"] = array (
      'rex_addon::isInstalled',
      '' 
    );
    /* OOArticle */
    $this->mapping ['OOArticle'] ["getArticleById"] = array (
      'rex_article::get',
      '' 
    );
    /* OOCategory */
    $this->mapping ['OOCategory'] ["getCategoryById"] = array (
      'rex_category::get',
      '' 
    );
    /* OOMedia */
    $this->mapping ['OOMedia'] ["getMediaByFileName"] = array (
      'rex_media::get',
      '' 
    );
    /* OOMediaCategory */
    $this->mapping ['OOMediaCategory'] ["getCategoryById"] = array (
      'rex_media_category::get',
      '' 
    );
    /* $REX */
    $this->mapping ['dollar_rex'] ["REDAXO"] = array (
      'rex::isBackend()',
      '' 
    );
    // dummy
    $this->mapping ['dollar_rex'] ["COM_USER"] = array (
      'rex::getComUser()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["START_ARTICLE_ID"] = array (
      'rex_article::getSiteStartArticleId()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["SERVER"] = array (
      'rex::getServer()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["SERVERNAME"] = array (
      'rex::getServerName()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["ERROR_EMAIL"] = array (
      'rex::getErrorEmail() ',
      '' 
    );
    $this->mapping ['dollar_rex'] ["HTDOCS_PATH"] = array (
      'rex_path::base()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["CUR_CLANG"] = array (
      'rex_clang::getCurrentId()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["CLANG"] = array (
      'rex_clang::getAll()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["TABLE_PREFIX"] = array (
      'rex::getTablePrefix()',
      '' 
    );
    $this->mapping ['dollar_rex'] ["USER"] = array (
      'rex::getUser()',
      '' 
    );
    /* REX */
    $this->mapping ['rex'] ["REX_HTML_VALUE"] = array (
      'REX_VALUE',
      '[id=$value output=html]' 
    );
    $this->mapping ['rex'] ["REX_PHP"] = array (
      'REX_VALUE',
      '[id=$value output=php]' 
    );
    $this->mapping ['rex'] ["REX_LINK_BUTTON"] = array (
      'REX_LINK',
      '[id=$value widget=$value]' 
    );
    $this->mapping ['rex'] ["REX_LINKLIST_BUTTON"] = array (
      'REX_LINKLIST',
      '[id=$value widget=$value]' 
    );
    $this->mapping ['rex'] ["REX_MEDIA_BUTTON"] = array (
      'REX_MEDIA',
      '[id=$value widget=$value]' 
    );
    $this->mapping ['rex'] ["REX_MEDIALIST_BUTTON"] = array (
      'REX_MEDIALIST',
      '[id=$value widget=$value]' 
    );
    /* VALUE (molule input only) */
    $this->mapping ["VALUE"] = array (
      'REX_INPUT_VALUE',
      '' 
    );
  }

  function convertModules() {
    $mods = $this->db->getArray("SELECT `id` FROM `rex_module`;");
    foreach($mods as $mod) {
      $this->convertModuleById($mod['id']);
    }
  }

  function convertTemplates() {
    $temps = $this->db->getArray("SELECT `id` FROM `rex_template`;");
    foreach($temps as $temp) {
      $this->convertTemplateById($temp['id']);
    }
  }
  
  function convertModuleById($id) {
    $mod = $this->db->getArray ( "SELECT * FROM `rex_module` WHERE `id`=$id;" );
    $input = addslashes ( $this->makeUp ( stripslashes ( $mod [0] ['input'] ), array (
      'dollar_rex',
      'rex',
      'VALUE',
      'OOAddon',
      'OOCategory',
      'OOMedia',
      'OOMediaCategory' 
    ) ) );
    $output = addslashes ( $this->makeUp ( stripslashes ( $mod [0] ['output'] ), array (
      'dollar_rex',
      'rex',
      'OOAddon',
      'OOCategory',
      'OOMedia',
      'OOMediaCategory' 
    ) ) );
    $this->db->setQuery ( "UPDATE `rex_module` SET `input`='$input', `output`='$output' WHERE `id`=$id;" );
  }
  
  function debugger($string) {
    $string = stripslashes($string);
    $converted = addslashes($this->makeUp($string, array(
      'OOAddon' )));
    return $converted;
  }
  
  function convertTemplateById($id) {
    $temp = $this->db->getArray ( "SELECT * FROM `rex_template` WHERE `id`=$id;" );
    $content = addslashes ( $this->makeUp ( stripslashes ( $temp [0] ['content'] ), array (
      'dollar_rex',
      'rex',
      'OOArticle',
      'OOCategory',
      'OOMedia',
      'OOMediaCategory' 
    ) ) );
    $this->db->setQuery ( "UPDATE `rex_template` SET `content`='$content' WHERE `id`=$id;" );
  }
  
  function makeUp($string, $maps) {
    $total = 0;
    foreach ( $maps as $map ) {
      $pattern = $this->pattern [strtolower($map)];
      $matches = array ();
      switch ($map) {
        case "dollar_rex" :
          preg_match_all ( $pattern, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
          foreach ( $matches as $match ) {
            $search = $match [0] [0];
            $replace = $this->mapping [$map] [$match [1] [0]][0];
            if ($replace) {
              $string = str_replace ( $search, $replace, $string );
            }
          }
          break;
        case "rex" :
          preg_match_all ( $pattern, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
          foreach ( $matches as $match ) {
            $search = $match [0] [0];
            $point = trim ( substr ( $search, 0, strpos ( $search, "[" ) ) );
            $replace = $this->mapping [$map] [$point] [0] . str_replace ( '$value', $match [1] [0], $this->mapping [$map] [$point] [1] );
            if ($this->mapping [$map] [$point] [0]) {
              $string = str_replace ( $search, $replace, $string );
            }
          }
          break;
        case "VALUE" :
          $count = 0;
          $replace = $this->mapping[$map][0];
          $string = preg_replace_callback($pattern, function ($treffer) {return substr($treffer[0],0,1)."REX_INPUT_VALUE";}, $string,-1,$count);
          $total += $count;
          break;
        case "OOAddon" :
          preg_match_all ( $pattern, $string, $matches, PREG_SET_ORDER );
          $pieces = array();
          foreach ( $matches as $match ) {
            $search = $match[0];
            $replace = $this->mapping [$map][$match [1]][0]."('".$match [2]."')";
            if ($this->mapping [$map][$match [1]][0]) {
              $string = str_replace ( $search, $replace, $string );
            }
          }
          break;
        case "OOArticle" :
        case "OOCategory" :
        case "OOMedia" :
        case "OOMediaCategory" :
          preg_match_all ( $pattern, $string, $matches, PREG_SET_ORDER );
          foreach ( $matches as $match ) {
            $search = $map."::".$match[1]; 
            $replace = $this->mapping [$map][$match [1]][0];
            if ($this->mapping [$map][$match [1]][0]) {
              $string = str_replace ( $search, $replace, $string );
            }
          }
          break;
      }
    }
    return $string;
  }
}
?>