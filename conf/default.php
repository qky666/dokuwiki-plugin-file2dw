<?php
/**
 * Default settings for the file2dw plugin
 *
 * @author José Torrecilla <qky669@gmail.com>
 */

$conf['debugShowInfo']            = 0; 
// debug show info messages: 0: no; 1: yes; -- Error messages are always shown

$conf['logFile']                  = '/var/log/file2dw.log';                 
// log file where $this->_msg write

$conf['logLevel']                 = 'error';
// log level: what $this->_msg write to the log file 


$conf['formDisplayRule']          = 'file2dw';
// which action will display the file2dw upload form

$conf['formIntroMessage']         = 'default';
// personalized message - if "default", display the language default message

$conf['formMaxFileSize']          = 4194304; 
// maxsize for userFile upload


$conf['parserLinkToOriginalFile'] = 0;
// display a link to the original userFile 0=no link; 1=link

$conf['parserUploadDir']          = ''; 
// system path where the file will be moved after upload but before parse.
// If empty, system's default temp dir is used.

$conf['parserMimeTypeSOffice']    = 'application/msword';
// mimetypes that need SOffice conversion
