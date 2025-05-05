<?php

/**
 * Processes autoloader, that is different from the PSR-4 autoloader standard
 */

spl_autoload_register(function($class) {
  $exploded_classname = explode("\\", $class);
  $count_subprocesses = count($exploded_classname);
  $relative_classname = $exploded_classname[$count_subprocesses - 1];

  $directory_where_search = __DIR__ . DIRECTORY_SEPARATOR . "processes" . DIRECTORY_SEPARATOR;

  $_RDI = new RecursiveDirectoryIterator($directory_where_search);
  $_RII = new RecursiveIteratorIterator($_RDI);

  foreach ($_RII as $file) {
    if ($file->isDir()){ 
      continue;
    }

    $file_path = $file->getPathname();
    $file_name = $file->getFilename();
    if (is_file($file_path) && $file_name == $relative_classname.".php") {
      // echo "Require ---> " . $file_path . "<br>";  /* to see the require order of the autoloader */
      require_once $file_path;
    }
  }
});

?>