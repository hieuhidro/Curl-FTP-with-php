<?php
ini_set('max_execution_time', 0);

class Mage_Dataflow_Model_Convert_Adapter_Extend_Curl
{
    const EMPTY_HOST = "Can't find host to connect";
    const FTP_URL = 'ftp://';
    protected $_conn;
    protected $_curl;

    /*
     * function construct
     * Possible argument keys:
     * - host        required
     * - port        default 21
     * - timeout     default 60
     * - ssl         default false
     * - user        default anonymous
     * - password    default empty
     * - file_mode   default FTP_BINARY
     * @param array $args array('host', 'port' => 21, 'user', 'password', 'file_mode' => FTP_BINARY, 'timeout' => 60, 'ssl' => false);
     * @exception Exception
     */
    public function __construct(array $args = array())
    {
        $this->_conn['password'] = isset($args['password']) ? $args['password'] : '';
        if (!isset($args['host'])) {
            throw new Exception (self::EMPTY_HOST);
        } else {
            $this->_conn['host'] = $args['host'];
        }

        $this->_conn['user'] = isset($args['user']) ? $args['user'] : 'anonymous';
        $this->_conn['password'] = isset($args['password']) ? $args['password'] : '';
        $this->_conn['port'] = isset($args['port']) && $args['port'] ? $args['port'] : 21;
        $this->_conn['file_mode'] = isset($args['file_mode']) && $args['file_mode'] ? $args['file_mode'] : FTP_BINARY;
        $this->_conn['timeout'] = isset($args['timeout']) && $args['timeout'] ? $args['timeout'] : 60;
    }

    /**
     * @return mixed
     */
    protected function setDefault()
    {
        if ($this->_curl) {
            curl_setopt($this->_curl, CURLOPT_UPLOAD, 0);
            curl_setopt($this->_curl, CURLOPT_INFILE, null);
            curl_setopt($this->_curl, CURLOPT_INFILESIZE, -1);
            curl_setopt($this->_curl, CURLOPT_FTPLISTONLY, 0);
        }
        return $this->_curl;
    }

    /**
     * @return resource
     */
    protected function getCurl()
    {
        if (!$this->_curl) {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->_curl, CURLOPT_USERPWD, $this->_conn['user'] . ":" . $this->_conn['password']);
            curl_setopt($this->_curl, CURLOPT_PORT, $this->_conn['port']);
            if (isset($args['ssl']) && $args['ssl'] == true) {
                curl_setopt($this->_curl, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
            }
        }
        $this->setDefault();
        return $this->_curl;
    }

    public function __destruct()
    {
        if ($this->_curl) {
            curl_close($this->_curl);
        }
    }

    protected function get_headers_from_curl_response($response)
    {
        $headers = array();
        $header_text = preg_split('/\n|\r\n?/', $response);
        foreach ($header_text as $i => $line) {
            $basename = preg_replace('/^.+[\\\\\\/]/', '', $line);
            $ext = pathinfo($line, PATHINFO_EXTENSION);
            if ($ext) {
                $headers['file'][] = $basename;
            } else {
                $headers['folder'][] = $line;
            }
        }
        return $headers;
    }

    /**
     * Function get all file and  sub folder from folder
     * return array ('file' => array(), 'folder' => array());
     * if not exists file isset($return['file']) == true;
     * if not exists folder isset($return['folder']) == true;
     * @param string $folder
     * @return array
     */
    public function getListDir($folder = '')
    {
        if ($this->getCurl()) {
            curl_setopt($this->_curl, CURLOPT_URL, self::FTP_URL . $this->_conn['host'] . "/$folder/"); #input
            curl_setopt($this->_curl, CURLOPT_FTPLISTONLY, 1);
            $result = curl_exec($this->_curl);
            $headers = $this->get_headers_from_curl_response($result);
            return $headers;
        }
        return array();
    }

    public function getFileContent($destination)
    {
        $content = '';
        if (file_exists($destination)) {
            $content = file_get_contents($destination);
        }
        return $content;
    }

    /**
     * Function curlUpload($remote_file, $destination)
     * @param $remote_file from client
     * @param $destination file into ftp
     * @param $fileName name of file
     * @return bool
     * @throws Exception
     */
    public function curlUpload($remote_file, $destination, $fileName = '')
    {
        try {
            if (file_exists($remote_file)) {
                if (!empty($destination)) {
                    if ($this->getCurl()) {
                        if($fp = fopen($remote_file, 'r')){
                            curl_setopt($this->_curl, CURLOPT_URL, self::FTP_URL . $this->_conn['host'] . "/$destination/$fileName"); #input
                            curl_setopt($this->_curl, CURLOPT_UPLOAD, 1);
                            curl_setopt($this->_curl, CURLOPT_INFILE, $fp);
                            curl_setopt($this->_curl, CURLOPT_INFILESIZE, filesize($remote_file));
                            curl_exec($this->_curl);
                            fclose($fp);
                            $error_no = curl_errno($this->_curl);
                            if ($error_no == 0) {
                                return true;
                            } else {
                                $error = curl_error($this->_curl);
                                throw new Exception ($error);
                            }
                        }else{
                            $error = "Can't read file.";
                            throw new Exception ($error);
                        }
                    }
                }
            } else {
                $error = "File doesn't exists. file: $remote_file";
                throw new Exception ($error);
            }
        } catch (Exception $e) {
            if($fp != null) {
                fclose($fp);
            }
            throw new Exception ($e->getMessage());
        }
        return false;
    }

    /**
     * @param $remote_file
     * @param $destination
     * @param bool $content return content of file default = true
     * @return bool|string
     * @throws Exception
     */
    public function curlGetFile($remote_file, $destination, $content = true)
    {
        try {
            if ($this->getCurl()) {
                if (!file_exists($destination)) {
                    $file = fopen($destination, 'w');
                    if (false === $file) {
                        throw new Exception("Can't create $destination");
                        return false;
                    }
                    curl_setopt($this->_curl, CURLOPT_URL,self::FTP_URL . $this->_conn['host'] . "/$remote_file"); #input
                    curl_setopt($this->_curl, CURLOPT_FILE, $file); #output
                    curl_exec($this->_curl);
                    curl_exec($this->_curl);
                    $error_no = curl_errno($this->_curl);
                    if ($error_no != 0) {
                        fclose($file);
                        $error = curl_error($this->_curl);
                        throw new Exception ($error . '. More: http://curl.haxx.se/libcurl/c/libcurl-errors.html');
                        return false;
                    }
                    if (file_exists($destination)) {
                        if ($content) {
                            $content = $this->getFileContent($destination);
                            fclose($file);
                            return $content;
                        } else {
                            fclose($file);
                            return true;
                        }
                    }
                    fclose($file);
                } else {
                    if ($content) {
                        $content = $this->getFileContent($destination);
                        return $content;
                    } else {
                        return true;
                    }
                }
            } else {
                return false;
            }
        } catch (Exception $e) {

            throw new Exception($e);
        }
        return false;
    }
}
