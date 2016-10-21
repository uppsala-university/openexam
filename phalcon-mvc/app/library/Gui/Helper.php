<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Helper.php
// Created: 2014-11-06 17:50:05
// 
// Author:  Ahsan Shahzad (MedfarmDoIT, Uppsala University)
// 

namespace OpenExam\Library\Gui;

use Phalcon\Mvc\User\Component;

/**
 * Views helper component
 * Formats data to be easily useable in views
 *
 * @author Ahsan Shahzad (MedfarmDoIT, Uppsala University)
 */
class Helper extends Component
{

        /**
         * Format data returned from catalog service to make it useable in views,
         * in a better and clean way. 
         *
         * @param string $principal
         * @param string $attribute
         * @param string $firstOnly Return only first record, if set true
         * @return array
         * @deprecated since version 2.0.0
         */
        public function getCatalogAttribute($principal, $attribute, $firstOnly = TRUE)
        {
                $data = array();
                $userData = $this->catalog->getAttribute($principal, $attribute);

                //search for info from all services
                foreach ($userData as $service) {
                        $data = $userData[0][$attribute];
                        if (count($data)) {
                                break;
                        }
                }

                return $firstOnly ? $data[0] : $data;
        }

        /**
         * Progresively download a large file
         * 
         * @param string $filePath
         * @depends finfo extension
         */
        public function downloadFile($filePath)
        {
                if (!empty($filePath)) {

                        $fileInfo = pathinfo($filePath);
                        $fileName = $fileInfo['basename'];
                        $fileExtnesion = $fileInfo['extension'];
                        $contentType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);

                        if (file_exists($filePath)) {
                                $this->view->disable();

                                $size = filesize($filePath);
                                $offset = 0;
                                $length = $size;

                                //headers for partial download
                                if (isset($_SERVER['HTTP_RANGE'])) {

                                        preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
                                        $offset = intval($matches[1]);
                                        $length = intval($matches[2]) - $offset;
                                        $fhandle = fopen($filePath, 'r');
                                        fseek($fhandle, $offset); // seek to the requested offset, this is 0 if it's not a partial content request 
                                        $data = fread($fhandle, $length);
                                        fclose($fhandle);
                                        header('HTTP/1.1 206 Partial Content');
                                        header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $size);
                                }

                                header("Content-Disposition: attachment;filename=" . $fileName);
                                header('Content-Type: ' . $contentType);
                                header("Accept-Ranges: bytes");
                                header("Pragma: public");
                                header("Expires: -1");
                                header("Cache-Control: no-cache");
                                header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                                header("Content-Length: " . filesize($filePath));
                                $chunksize = 8 * (1024 * 1024); //8MB (highest possible fread length) 

                                if ($size > $chunksize) {

                                        $handle = fopen($filePath, 'rb');
                                        $buffer = '';
                                        while (!feof($handle) && (connection_status() === CONNECTION_NORMAL)) {
                                                $buffer = fread($handle, $chunksize);
                                                print $buffer;
                                                ob_flush();
                                                flush();
                                        }

                                        if (connection_status() !== CONNECTION_NORMAL) {
                                                echo "Connection aborted";
                                        }

                                        fclose($handle);
                                } else {

                                        ob_clean();
                                        flush();
                                        readfile($filePath);
                                }
                        } else {
                                echo 'File does not exist!';
                        }
                } else {
                        echo 'There is no file to download!';
                }
        }

}
