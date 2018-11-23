<?php
/**
 * Options for the file2dw plugin
 *
 * @author José Torrecilla <qky669@gmail.com>
 */

$meta['debugShowInfo']            = array('onoff');
$meta['logFile']                  = array('string');
$meta['logLevel']                 = array('multichoice', '_choices' => array(0,1,2));

$meta['formDisplayRule']          = array('multicheckbox', '_choices' => array( 'file2dw', 'edit', 'show' ) );
$meta['formIntroMessage']         = array('');
$meta['formMaxFileSize']          = array('numericopt');

$meta['parserLinkToOriginalFile'] = array('onoff');
$meta['parserUploadDir']          = array('string');
$meta['parserMimeTypeSOffice']    = array('string');
