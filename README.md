# Curl-FTP-with-php
Download file from ftp with php without timeout, file large, curl ftp download file, list file from ftp with curl, 

<h2>With Magento project</h2>
**Copy file and folder to project** 
goto shell and run cmd
<br/>1: Xampp --> Shell commander
<br/>2: order --> Run command line with what you know. 

<br/><code> php -f ftp_download.php --host "ftp.yourhost.com" --user "ussername" --pw "password" --sr "/" --dest "/" </code>

More option Usage:  
<pre>
php ftp_download.php -- [options]
--host    [value]      Ftp host connection. (required)		
--hp      [value]      Ftp Port connection. (Default 21)	
--user    [value]      User login.          (default anonymou)	
--pw      [value]      Password login ftp.  (default empty)	
--ssl     [value]      Default false (true/false)		
--fm      [value]      Default FTP_BINARY			
--timeout [value]      Default 60				
--sr      [value]      Folder download.     (required)		
--dest    [value]      Folder save to.      (required)	
help                   This help
</pre>

**With order project php** Use class **Mage_Dataflow_Model_Convert_Adapter_Extend_Curl**;
<pre>
$connect = array(
                'host' => $host,
                'port' => $port,
                'user' => $user,	  
                'password' => $password,  
                'ssl' => $ssl,		  // default false 
                'file_mode' => $filemode, // default FTP_BINARY
                'timeout' => $timeout,
            );
            if($sourceFolder && $destFolder){
		$CurlFtp = new Mage_Dataflow_Model_Convert_Adapter_Extend_Curl($connect);
                $lsDir = $CurlFtp->getListDir($sourceFolder);
                if(!is_dir($destFolder)){
                    mkdir($dest,'0777',true);
                }
                if(isset($lsDir['file'])){
                    $i = 0;
                    foreach($lsDir['file'] as $key => $file){
                        if($i == 4) break;
                        echo "Downloading: $file \r\n";
                        if($this->_getFtp()->curlGetFile($sourceFolder.DS.$file,$destFolder.'/'.$file, false)){
                            echo "Download successful File: $file \r\n";
                        }
                    }
                }else{
                    echo "Doesn't exists any file in folder";
                }
            }
</pre>
