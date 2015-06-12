<?php
require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 * Download File: php ftp_download.php --host ftp.acusport.com --user "UserName" --pwd "Password" --src Ouput --dest ../media/import
 * Upload File: php ftp_download.php --host ftp.acusport.com --user "UserName" --pwd "Password" --upload --src Ouput --dest ../media/import
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Compiler extends Mage_Shell_Abstract
{
    protected $_Ftpcurl;

    protected function _getFtp(array $args = array())
    {
        if (!$this->_Ftpcurl) {
            $this->_Ftpcurl = Mage::getModel('dataflow/convert_adapter_extend_curl', $args);
        }
        return $this->_Ftpcurl;
    }

    protected function getLstFile($dir){
        //$dirPath = Mage::getBaseDir().str_replace('/',DS,$dir);
        $fileLst = array();
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $ext = pathinfo($entry, PATHINFO_EXTENSION);
                    if($ext){
                        $fileLst[] = $entry;
                    }
                }
            }
            closedir($handle);
        }
        return $fileLst;
    }

    public function run()
    {
        $_SESSION = array();
        if ($this->getArg('host') && $this->getArg('src') && $this->getArg('dest')) {
            $connect = array(
                'host' => $this->getArg('host'),
                'port' => $this->getArg('port'),
                'user' => $this->getArg('user'),
                'password' => $this->getArg('pwd'),
                'ssl' => $this->getArg('ssl'),
                'file_mode' => $this->getArg('fm'),
                'timeout' => $this->getArg('timeout'),
            );

            if (is_string($this->getArg('src')) && is_string($this->getArg('dest'))) {
                $sour = $this->getArg('src');
                $dest = $this->getArg('dest');

                $myCurl =  $this->_getFtp($connect);
                if($myCurl  instanceof Mage_Dataflow_Model_Convert_Adapter_Extend_Curl) {
                    if (!$this->getArg('upload')) {
                        $lsDir = $myCurl->getListDir($this->getArg('src'));
                        if (!is_dir($dest)) {
                            if (!mkdir($dest, 0777, true)) {
                                echo "Can't create colder" . "\r\n";
                                return;
                            }
                        }
                        if (isset($lsDir['file'])) {
                            $i = 0;
                            foreach ($lsDir['file'] as $key => $file) {
                                echo "Download file: $file";
                                try {
                                    if ($this->_getFtp()->curlGetFile($sour . DS . $file, $dest . '/' . $file, false)) {
                                        echo "Download file $file completed.\r\n";
                                    }
                                } catch (Exception $e) {
                                    echo $e->getMessage() . "\r\n";
                                }
                            }
                        } else {
                            echo "No Files or Folders found!\r\n";
                        }
                    } else {
                        $lstFile = $this->getLstFile($sour);
                        echo "Upload all file from $sour to $dest \r\n";
                        if (count($lstFile)) {
                            echo "Found ".count($lstFile)." file(s) \r\n";
                            foreach ($lstFile as $key => $fileName) {
                                echo "Uploading file: $fileName \r\n";
                                try{
                                    if($myCurl->curlUpload($sour.'/'.$fileName,$dest,$fileName)){
                                        echo "Upload file $fileName completed \r\n";
                                    }else{
                                        echo "Upload file $fileName fail \r\n";
                                    }
                                }catch (Exception $e){
                                    echo $e->getMessage()."\r\n";
                                }
                        }
                        } else {
                            echo "No Files or Folders found!\r\n";
                        }
                    }
                }
            }
        } else {
            echo $this->usageHelp();
        }
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ftp_download.php -- [options]
  --host    <value>      Ftp host (required)
  --port    <value>      Ftp Port (Default 21)
  --user    <value>      Ftp Username          (default anonymous)
  --pwd     <value>      Ftp Password.  (default empty)
  --ssl     <value>      Default false (true/false)
  --fm      <value>      Default FTP_BINARY
  --timeout <value>      Default 60
  --upload  <value>      Upload all file from src to dest. Default false
  --src     <value>      Download from folder.     (required)
  --dest    <value>      Folder save to.      (required)
  help                   This help
USAGE;
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();
