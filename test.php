<?php
 *    include 'SFTP/vendor/autoload.php';
 *
 *    $sftp = new \phpseclib\Net\SFTP('23.122.104.252');
 *    if (!$sftp->login('ocr_lrwic', 'the_proxy@OCR')) {
 *        exit('Login Failed');
 *    }
 *
 *    echo $sftp->pwd() . "\r\n";
 *    $sftp->put('filename.ext', 'hello, world!');
 *    print_r($sftp->nlist());
 * ?>