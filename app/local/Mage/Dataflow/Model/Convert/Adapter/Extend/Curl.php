<?php
/**
 * Copy right by Hoang Hieu
 * skype: hieu_hidro
 * email: hieu.gh@gmail.com
 */
ini_set('max_execution_time', 0);

class Mage_Dataflow_Model_Convert_Adapter_Extend_Curl
{
    const EMPTY_HOST = "Can't find host to connect, Params Host required";

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
        $this->getCurl();
    }

    protected function getCurl()
    {
        if (!$this->_curl) {
            $this->_curl = curl_init();
            curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->_curl, CURLOPT_USERPWD, $this->_conn['user'] . ":" . $this->_conn['password']);
            curl_setopt($this->_curl, CURLOPT_PORT, $this->_conn['port']);
            //curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->_conn['timeout']);
            if (isset($args['ssl']) && $args['ssl'] == true) {
                curl_setopt($this->_curl, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
            }
        }
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
        $header_text = explode("\r\n",$response);
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
     * return array ('file' => array(), 'folder' => array()); \r\n
     * if not exists file isset($return['file']) == true; \r\n
     * if not exists folder isset($return['folder']) == true; \r\n
     * @param string $folder
     * @return array
     */
    public function getListDir($folder = '')
    {
        if ($this->getCurl()) {
            curl_setopt($this->_curl, CURLOPT_URL, "ftp://" . $this->_conn['host'] . "/$folder/"); #input
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
                if(!file_exists($destination)){
                    $file = fopen($destination, 'w');
                    curl_setopt($this->_curl, CURLOPT_URL, "ftp://" . $this->_conn['host'] . "/$remote_file"); #input
                    curl_setopt($this->_curl, CURLOPT_FILE, $file); #output
                    curl_setopt($this->_curl, CURLOPT_FTPLISTONLY, 0);
                    curl_exec($this->_curl);
                    if (file_exists($destination)) {
                        if($content) {
                            $content = $this->getFileContent($destination);
                            fclose($file);
                            return $content;
                        }else{
                            fclose($file);
                            return true;
                        }
                    }
                    fclose($file);
                }else{
                    if($content) {
                        $content = $this->getFileContent($destination);
                        return $content;
                    }else{
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