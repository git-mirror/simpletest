<?php

class CoverageUtils {
  
  static function mkdir($dir) {
    if (!file_exists($dir)) {
      mkdir($dir, 0777, True);
    } else {
      if (!is_dir($dir)) {
        throw new Exception($dir .' exists as a file, not a directory');
      }
    }
  }
  
}
?>