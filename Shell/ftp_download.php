<?php
require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
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

    public function run()
    {
        $_SESSION = array();
        if ($this->getArg('host') && $this->getArg('sr') && $this->getArg('dest')) {
            $connect = array(
                'host' => $this->getArg('host'),
                'port' => $this->getArg('hp'),
                'user' => $this->getArg('user'),
                'password' => $this->getArg('pw'),
                'ssl' => $this->getArg('ssl'),
                'file_mode' => $this->getArg('fm'),
                'timeout' => $this->getArg('timeout'),
            );
            if(is_string($this->getArg('sr')) && is_string($this->getArg('dest'))){
                $sour = $this->getArg('sr');
                $dest = $this->getArg('dest');

                $mycurl = $this->_getFtp($connect);
                $lsDir = $mycurl->getListDir($this->getArg('sr'));
                if(!is_dir($dest)){
                    mkdir($dest,'0777',true);
                }
                if(isset($lsDir['file'])){
                    $i = 0;
                    foreach($lsDir['file'] as $key => $file){
                        if($i == 4) break;
                        echo "Downloading: $file \r\n";
                        if($this->_getFtp()->curlGetFile($sour.DS.$file,$dest.'/'.$file, false)){
                            echo "Download successful File: $file \r\n";
                        }
                    }
                }else{
                    echo "Doesn't exists any file in folder";
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
  --host    <value>      Ftp host connection. (required)
  --hp      <value>      Ftp Port connection. (Default 21)
  --user    <value>      User login.          (default anonymous)
  --pw      <value>      Password login ftp.  (default empty)
  --ssl     <value>      Default false (true/false)
  --fm      <value>      Default FTP_BINARY
  --timeout <value>      Default 60
  --sr      <value>      Folder download.     (required)
  --dest    <value>      Folder save to.      (required)
  help                   This help
USAGE;
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();
