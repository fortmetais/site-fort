<?php
function fort_validate_php_sources() {
    $count = 0;
    foreach (array('/wordpress/wp-content/themes/fort-metais', '/wordpress/wp-content/plugins/fort-catalogo', '/fort-tools') as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') continue;
            try { token_get_all(file_get_contents($file->getPathname()), TOKEN_PARSE); }
            catch (ParseError $error) { throw new RuntimeException($file->getPathname() . ': ' . $error->getMessage()); }
            $count++;
        }
    }
    echo 'PHP validado: ' . $count . ' arquivos.';
}
