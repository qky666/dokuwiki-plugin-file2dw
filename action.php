<?php
/**
 * Action Plugin
 *
 * @license     MIT License (https://opensource.org/licenses/MIT)
 * @author      José Torrecilla <qky669@gmail.com>
 * @version     0.1beta
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class action_plugin_file2dw extends DokuWiki_Action_Plugin {

  /**
  * Registers a callback function for a given event
  */
  function register(Doku_Event_Handler $controller) {
    
    // File parser hook
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_parser', array());
    
    // Display form hook before the wiki page (on top)
    $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, '_render', array());
    
    //Add MENU_ITEMS_ASSEMBLY 
    $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, '_addsvgbutton', array());
  }

  /**
   * Add 'import'-button to menu
   *
   * @param Doku_Event $event
   * @param mixed      $param not defined
   */
  function _addsvgbutton(&$event, $param) {
    if($event->data['view'] == 'page') {
      array_push($event->data['items'],new \dokuwiki\plugin\file2dw\MenuItem());
    }
  }

  /**
   * Displays the upload form in the pages according to authorized action
   *
   * @param Doku_Event $event It's a dokuwiki event function
   * @param mixed      $param Not defined
   */
  function _render(&$event, $param) {
    // $ID: Page identifier
    global $ID;
    
    // Check if should display the form
    if ( strpos( $this->getConf('formDisplayRule'), $event->data) === false ) return;

    // If the page exists but $event->data != "file2dw", return
    if ( page_exists( $ID ) && $event->data != "file2dw" ) return;
    
    // Check auth user can edit this page
    if ( auth_quickaclcheck( $ID ) < AUTH_EDIT ) return;
    
    // If page exists, show warning to the user
    if ( page_exists( $ID ) ) echo p_render('xhtml',p_get_instructions( $this->getLang( 'formPageExistMessage' ) ), $info );
    
    // Show form
    echo $this->_createForm();

    if ( $event->data == 'file2dw' ) $event->preventDefault();
    
  }

  /**
   * Creates an returns a string with the HTML upload form to show to the user
   * 
   * @return string HTML upload form
   */
  function _createForm() {
 
    global $ID;

    $form = new dokuwiki\Form\Form(array('id' => 'file2dw_form', 'enctype' => 'multipart/form-data'));
    
    // Intro message
    $message = $this->getConf('formIntroMessage');
    if ( $message == 'default' ) $message = $this->getLang('formIntroMessage');
    if ( $message ) {
        $message = p_render('xhtml',p_get_instructions($message),$info);
        $form->addHTML($message);
    }
    
    //Open fieldset
    $form->addFieldsetOpen();
    
    //legend tag
    $legend = $form->addTag('legend');
    $legend->attr('value',$this->getLang('formLegend'));

    //hidden
    $form->setHiddenField('MAX_FILE_SIZE',$this->getConf('formMaxFileSize'));
    $form->setHiddenField('do','file2dw');
    $form->setHiddenField('id',$ID);

    //userFile file input
    $userFileInputElement = new dokuwiki\Form\InputElement('file','userFile');
    $form->addElement($userFileInputElement);
    
    // submit
    $submitInputElement = new dokuwiki\Form\InputElement('submit','btn_upload');
    $submitInputElement->attr('value',$this->getLang('import_button'));
    $form->addElement($submitInputElement);

    //Close fieldset
    $form->addFieldsetClose();
    
    return $form->toHTML();
  }

  
  /**
   * Checks if the file might be uploaded, then call the file2dw converter
   *
   * @param Doku_Event $event It's a dokuwiki event function
   * @param mixed      $param Not defined
   */
  function _parser(&$event, $param) {
    
    // Check action is file2dw
    if ( $event->data != 'file2dw' ) return;

    // Preparation of the message renderer
    // Set the debug lvl
    $this->logLevel = $this->getConf( 'logLevel' );
    $this->debugShowInfo = $this->getConf( 'debugShowInfo' );
    //If used, open the logFile
    if ( $this->logLevel > 0 ) {
      $this->logFile = $this->getConf( 'logFile' );
      if ( isset( $this->logFile ) ) {
        if ( file_exists( dirname( $this->logFile ) ) || mkdir( dirname( $this->logFile ) ) ) {
          if ( ! ( $this->logFileHandle = @fopen( $this->logFile, 'a' ) ) ) {
            unset( $this->logFileHandle, $this->logFile );
          }
        } else {
          unset( $this->logFile );
        }
      }
      if ( ! isset( $this->logFileHandle ) ) {
        $this->_msg('er_logFile');
      }
    }

    // Check upload file defined
    $retorno = false;
    if ( $_FILES['userFile'] && $_FILES['userFile']['error'] == 0 ) {
      $this->_msg( array('ok_info','userFile found: '.$_FILES['userFile']['name']) );
      // If parse work, change action to defined one in conf/local.php file
      $retorno = $this->_file2dw();
      // Delete temp folder
      $this->_purge_env();
    }
    
    // if the file is correctly parsed, change the action to "show"
    // otherwise the action stay file2dw
    if ( $retorno === true ) {
      $event->data = 'show';
    } else {
      $event->preventDefault();
    }

    // Clear the message renderer
    // Close the log file if used
    if ( isset( $this->logFileHandle ) ) {
      @fclose( $this->logFileHandle );
    }
  }


  /**
   * Converts uploaded file to Dokuwiki syntax
   *
   * @return bool true if conversion ended ok; false if conversion failed
   */
  function _file2dw() {
    
    global $ID;
    
    ### Check parameter ###
    
    // Page receive content
    if ( ! $this->pageName = $ID ) return $this->_msg('er_id');
    $this->nsName = getNS($this->pageName);
    // Check rights to change the page
    if ( page_exists($ID) ) {
      if ( auth_quickaclcheck($ID) < AUTH_EDIT ) return $this->_msg('er_acl_edit');
    } else {
      if ( auth_quickaclcheck($ID) < AUTH_CREATE ) return $this->_msg('er_acl_create');
    }
    
    // Check uploaded file
    $this->_checkUploadedFile();
    
    // Need OpenOffice conversion?
    // workFile is the file that will be converted by pandoc to dokuwiki syntax
    // workFile is the userFile by default
    // It will be changed if OpenOffice conversion is needed (example: .doc files)
    $this->workFileName = substr($this->userFileName,0);
    $this->workFile = substr($this->userFile,0);
    if ($this->getConf( 'parserMimeTypeSOffice' ) != '' 
        && strpos( $this->getConf( 'parserMimeTypeSOffice' ), $_FILES['userFile']['type'] ) !== false) {
      if ( !$this->_OOConversion() ) return false;
    }
    
    // pandoc conversion
    // Resulting file name: dwpage
    // Images folder: img
    $this->dwpageFileName = 'dwpage';
    $this->dwpageFile = $this->workDir.'/'.$this->dwpageFileName;
    $this->dwimgDir = $this->workDir.'/img'; 
    $output = array();
    $command = 'pandoc -s -w dokuwiki --extract-media="'.$this->dwimgDir;
    $command .= '" -o "'.$this->dwpageFile.'" "'.$this->workFile.'"';
    exec( $command, $output, $return_var );
    
    $this->_msg(array('ok_info','Executed command: '.$command));
    
    if ( !file_exists($this->dwpageFile) ) {
      $message = '<br>Missing file: ' . $this->dwpageFile;
      $message .= '<br>Command: ' . $command;
      $message .= '<br>Output: '. print_r($output,true);
      $message .= '<br>Return: '. $return_var;
      return $this->_msg( array('er_pandoc',$message) );
    }
    
    $this->_msg(array('ok_info','pandoc conversion done'));
    
    
    // Initial result
    $this->result = '====== '.basename($this->userFileName).' ======
';
    if ( $this->getConf('parserLinkToOriginalFile') && auth_quickaclcheck($ID) >= AUTH_UPLOAD ) { 
      $this->result .= '<sub>{{'.$this->userFileName.'|'.$this->getLang('parserOriginalFile').'}}</sub>

';
    }
    
    $this->result .= file_get_contents ($this->dwpageFile);
    
    // If dwimgDir does not exist, we do not need to porcess it
    if (is_dir($this->dwimgDir)) {
      $this->_msg(array('ok_info','Start processing dir '.$this->dwimgDir));
      // Use $this->now to put a timestamp in images name
      $this->now = date('Y-m-d_H-i-s');
      // Use $this->importedImages to count (and store, if we need to delete them after an error)
      $this->importedImages = array();
      if ( !$this->_processImgDir($this->dwimgDir) ) {
        //Delete all imported images until error from dokuwiki
        foreach ($this->importedImages as $imgId) {
          media_delete($imgId, null);
        }
        // Return error
        return $this->_msg('er_img_dir');
      }
    }
    
    $this->_msg(array('ok_info','Resultado: '.$this->result));
    
    // Keep the original file (import the upload file in the mediaManager)
    if ( auth_quickaclcheck($ID) >= AUTH_UPLOAD ) {
      $destFile = mediaFN( $this->nsName.':'.$this->userFileName );
      list( $ext, $mime ) = mimetype($this->userFile);
      if ( media_upload_finish($this->userFile, $destFile, $this->nsName, $mime, @file_exists($destFile), 'rename' ) != $this->nsName ) {
        return $this->_msg( array( 'er_apply_file' ) );
      }
    } else {
      // If not allowed to upload, return error.
      return $this->_msg('er_acl_upload');
    }
    
    // Save wiki page
    saveWikiText( $this->pageName, $this->result, $this->getLang( 'parserSummary' ).$this->userFileName );
    if ( ! page_exists($this->pageName) ) return $this->_msg('er_apply_content');
    
    return true;
  }


  /**
   * Add images in a directory (and its subdirectories) to Dokuwiki mediaManager. 
   * Also updates $this->result (it will be wiki page content).
   *
   * @param string $imgDir Full path directory to process
   * @return bool true if process ended ok; false if failed
   */
  function _processImgDir($imgDir) {
    
    // In $imgDir is not a directory, return error
    if (!is_dir($imgDir)) return $this->_msg(array('er_img_dir',$imgDir.' is not a directory'));
    
    // list and process directory items
    $items = array_diff(scandir($imgDir), array('.','..'));
    foreach ($items as $item) {
      $itemPath = "$imgDir/$item";
      if (is_dir($itemPath)) {
        if (!$this->_processImgDir($itemPath)) {
          return $this->_msg(array ('er_img_dir','Error processing directory '.$itemPath) );
        }
      } else {
        if (!$this->_processImg($itemPath)) {
          return $this->_msg(array('er_img_dir','Error processing image '.$itemPath));
        }
      }
    }
        
    $this->_msg(array('ok_info','Processed image directory: '.$imgDir));
    
    return true;
  } 
  
  /**
   * Add single image to Dokuwiki mediaManager. 
   * Also updates $this->result (it will be wiki page content).
   *
   * @param string $imgPath Full path image to process
   * @return bool true if process ended ok; false if failed
   */
  function _processImg($imgPath) {

    list( $ext, $mime ) = mimetype( $imgPath );
    
    // Sanitize original file name
    $userFileBasename = basename($this->userFileName);
    $userFileBasename = mb_ereg_replace("([^\w\d\-_\[\]\(\)])", '_', $userFileBasename);
    $userFileBasename = mb_ereg_replace("(_{1,})", '_', $userFileBasename);
        
    // Trying to get a meaningful and unique file name
    // It will be something like "Uploaded_file_docx_2018-11-24_23-00-00_img1.jpg"
    $imgBasename = $userFileBasename.'_'.$this->now.'_img'.strval( count($this->importedImages)+1 ).'.'.$ext;
    $imgId = $this->nsName.':'.$imgBasename;
    $destFile = mediaFN( $imgId );
    
    // Add to mediaManagerif authorized
    if ( auth_quickaclcheck($ID) >= AUTH_UPLOAD ) {
      // Import the image file in the mediaManager (data/media)
      $destDir = mediaFN( $this->nsName );
      if ( ! ( file_exists( $destDir ) || mkdir( $destDir, 0777, true ) ) ) {
        return $this->_msg( array( 'er_dirCreate', 'Directory: '.$destDir ) );
      }
      
      // This works, but do not know if it is a hack... Meybe it can be done other way?
      $mediaReturn = media_upload_finish($imgPath, $destFile, $this->nsName, $mime, @file_exists($destFile), 'rename' );
      
      if ( $mediaReturn == $this->nsName ) {
        // "Upload" OK
        $this->importedImages[] = $imgId;
        // Replace string in result
        $this->result = str_replace( '{{'.$imgPath, '{{:'.$imgId, $this->result );
      } else {
        // Return error
        return $this->_msg( array( 'er_img_upload', 'Image: '.$imgPath.' Return: '.print_r($mediaReturn,true) ) );
      }
    } else {
      // If not allowed to upload, return error.
      return $this->_msg('er_acl_upload');
    }
  
    $this->_msg(array('ok_info','Processed image: '.$imgPath));
    
    return true;
  }
  

  /**
   * Converts $this->userFile to odt and stores it in $this->workFile
   *
   * @return bool true if process ended ok; false if failed
   */
  function _OOConversion() {
    
    // Conversion to odt file
    $output = array();
    $command = 'cd ' . $this->workDir;
    $command .= ' && sudo soffice --nofirststartwizard --headless --convert-to odt:"writer8" "' . $this->userFileName . '"';
    $return_var = shell_exec( $command );
    
    // Change original extension to ".odt"
    $info = pathinfo($this->userFile);
    $this->workFileName = $info['filename'] . '.odt';
    $this->workFile = $this->workDir.'/'. $this->workFileName;
    
    if ( !file_exists($this->workFile) ) {
      $message = '<br>Missing file: ' . $this->workFile;
      $message .= '<br>Command: ' . $command;
      $message .= '<br>Return: '. $return_var;
      return $this->_msg( array('er_soffice',$message) );
    }
    
    $this->_msg(array('ok_info','Open Office conversion done'));
    
    return true;
  }
  
  /**
   * Move uploaded file to a temp directory
   *
   * @return bool true if process ended ok; false if failed
   */
  function _checkUploadedFile() {
    ### _checkUploadedFile : group all process about the uploaded file ### 
    # OUTPUT :
    #   * true -> process successfully
    #   * false -> something wrong; using _msg to display what's wrong
    
    //Check if file exists
    if ( ! $_FILES['userFile'] ) return $this->_msg('er_file_miss');
    
    // Check the file status
    if ( $_FILES['userFile']['error'] > 0 ) {
      return $this->_msg( array( 'er_file_upload', $_FILES['userFile']['error'] ) );
    }

    // Removed: check file mimetype.
    // If pandoc can convert it, then it should work.
    // If not,then it should give an error
    
    // Create an unique temp work dir name
    $confUploadDir = $this->getConf('parserUploadDir');
    if ( !file_exists($confUploadDir) ) {
      $confUploadDir = null;
    }
    $this->workDir = $this->tempdir($confUploadDir, 'file2dw_', 0777);
    if ($this->workDir == false) {
      return $this->_msg('er_file_tmpDir');
    }
    chmod( $this->workDir, 0777 );

    // Move the upload file into the work directory
    $this->userFileName = $_FILES['userFile']['name'];
    $this->userFile = $this->workDir.'/'.$this->userFileName;
    if ( ! move_uploaded_file( $_FILES['userFile']['tmp_name'], $this->userFile ) ) { 
      return $this->_msg('er_file_getFromDownload');
    }
    
    $this->_msg( array('ok_info','userFile moved to '.$this->userFile) );
    
    return true;
  }

  /**
   * Display and/or log message using the debugLvl value
   *
   * @param string|array $message string: key for $this->getLang(); 
   *   array: $message[0]: string: key for $this->getLang(), $message[1]: string: additional information
   * @param int $type -1 -> error message, 0 -> normal message, 1 -> info message. 
   *   If null, the first 3 char of the key define the message type:er_ -> -1, ok_ -> 1, otherwise -> 0
   * @param bool $force force displaying the message without checking debugLvl
   * @return bool true -> Display normal message; false ->Display an error message
   */
  function _msg( $message, $type=null, $force=false ) {

    ### _msg : display message using the debugLvl value
    # $message : mixed :
    #   * string : key for $this->getLang() function
    #   * array :
    #       $message[0] : string : key for $this->getLang() function
    #       $message[1] : string : additional information
    # $type : integer : (check the dokuwiki msg function)
    #   * -1 : error message
    #   *  0 : normal message
    #   *  1 : info message
    # if type == null, the first 3 char of the key define the message type
    #   * er_ : -1
    #   * ok_ :  1
    #   * otherwise : 0
    # $force : boolean : force displaying the message without checking debugLvl
    # OUTPUT :
    #   * true -> display a normal message
    #   * false -> display an error message
    # DISPLAY : call dokuwiki msg function

    if ( is_array( $message ) ) {
      $output = $message[0];
    } else {
      $output = $message;
    }
    
    // If output is empty, crash with error display;
    if ( ! $output ) die( $this->getLang( 'er_msg_nomessage' ) );

    // If no $type defined, get it from key
    if ( is_null( $type ) ) {
      $val = substr( $output, 0, strpos( $output, '_' )+1 );
      switch ($val) {
        case 'er_' :
          $err = -1;
          break;
        case 'ok_' :
          $err = 1;
          break;
        default :
          $err = 0;
      }
    } else {
      if ( $type < -1 || $type > 1 ) return false;
      $err = $type;
    }

    // Message content
    $content = $output.' : '.$this->getLang( $output ).( is_array( $message ) ? ' : '.$message[1] : '' );
      
    // Determine if should show message 
    if ( $force || $this->debugShowInfo == 1 || $err == -1 ) {
      msg( 'file2dw : '.$content, $err );
    };

    //Determine if should log message
    if ( $this->logLevel > 0 && isset( $this->logFileHandle ) ) {
      fwrite( $this->logFileHandle, date(DATE_ATOM).':'.$_SERVER['REMOTE_USER'].':'.$content.' ' );
    };
      
    return ( $err == -1 ? false : true);

  }

  /**
   * Delete temp folder $this->workDir
   *
   * @return bool true if process ended ok; false if failed
   */
  function _purge_env() {

    if ( file_exists($this->workDir) ) {
      return $this->_delTree($this->workDir);
    }
    return true;

  }

  /**
   * Creates a random unique temporary directory, with specified parameters,
   * that does not already exist (like tempnam(), but for dirs).
   *
   * Created dir will begin with the specified prefix, followed by random
   * numbers.
   *
   * @link https://php.net/manual/en/function.tempnam.php
   *
   * @param string|null $dir Base directory under which to create temp dir.
   *     If null, the default system temp dir (sys_get_temp_dir()) will be
   *     used.
   * @param string $prefix String with which to prefix created dirs.
   * @param int $mode Octal file permission mask for the newly-created dir.
   *     Should begin with a 0.
   * @param int $maxAttempts Maximum attempts before giving up (to prevent
   *     endless loops).
   * @return string|bool Full path to newly-created dir, or false on failure.
   */
  function tempdir($dir = null, $prefix = 'tmp_', $mode = 0700, $maxAttempts = 1000)
  {
    /* Use the system temp dir by default. */
    if (is_null($dir))
    {
      $dir = sys_get_temp_dir();
    }
    
    /* Trim trailing slashes from $dir. */
    $dir = rtrim($dir, '/');
    
    /* If we don't have permission to create a directory, fail, otherwise we will
     * be stuck in an endless loop.
     */
    if (!is_dir($dir) || !is_writable($dir))
    {
      return false;
    }
    
    /* Make sure characters in prefix are safe. */
    if (strpbrk($prefix, '\\/:*?"<>|') !== false)
    {
      return false;
    }
      
    /* Attempt to create a random directory until it works. Abort if we reach
     * $maxAttempts. Something screwy could be happening with the filesystem
     * and our loop could otherwise become endless.
     */
    $attempts = 0;
    do
    {
      $path = sprintf('%s/%s%s', $dir, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (
      !mkdir($path, $mode) &&
      $attempts++ < $maxAttempts
    );
    
    
    return $path;
  }

  /**
   * Deletes (recursively) a directory that may not be empty
   *
   * @return bool true if process ended ok; false if failed
   */
  function _delTree($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? $this->_delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
  } 

}
