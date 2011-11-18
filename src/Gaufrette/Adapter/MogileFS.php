<?php

namespace Gaufrette\Adapter;

/**
 * Adapter for the MogileFS filesystem.
 *
 * @author Mikko Tarvainen 2011 <mtarvainen@gmail.com>
 *
 * Bases partly on Wikimedia MogileFS client code by Jens Frank and Domas Mituzas, 2007.
 * See more: http://svn.wikimedia.org/viewvc/mediawiki/trunk/extensions/MogileClient/
 */
class MogileFS extends Base
{
    protected $socket;
    protected $hosts;
    protected $domain;

    /**
     * Constructor
     *
     * @param domain MogileFS domain
     * @param hosts  Array of MogileFS trackers
     */
    public function __construct($domain, array $hosts)
    {
        if (strlen($domain) < 1 || count($hosts) < 1) {
            throw new \InvalidArgumentException('Invalid parameters. Given domain is too short or you not given any host.');
        }

        $this->domain = $domain;
        $this->hosts  = $hosts;
        $this->socket = null;
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        $data = '';
        $paths = $this->getPaths($key);

        if ($paths) {
            foreach ($paths as $path) {
                $fh = fopen($path, 'r');

                if (!$fh) {
                    continue;
                }

                while (!feof($fh)) {
                    $data .= fread($fh, 8192);
                }

                fclose($fh);
            }
        }

        return $data;
    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $closeres = false;

        if (mb_strlen($content) > 0) {
            $res = $this->doRequest("CREATE_OPEN", array("key" => $key, "class" => $metadata['mogile_class']));

            if ($res && preg_match('/^http:\/\/([a-z0-9.-]*):([0-9]*)\/(.*)$/', $res['path'], $matches)) {
                $host = $matches[1];
                $port = $matches[2];
                $path = $matches[3];

                $status = $this->putFile($res['path'], $content);

                if ($status) {
                    $params = array("key" => $key, "class" => $metadata['mogile_class'], "devid" => $res['devid'],
                                    "fid" => $res['fid'], "path" => urldecode($res['path']));
                    $closeres = $this->doRequest("CREATE_CLOSE", $params);
                }
            }
        }

        if (!is_array($closeres)) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file in \'%s\'.',$key, $metadata['mogile_class']));
        }

        return mb_strlen($content);
    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {
        $res = $this->doRequest("DELETE", array("key" => $key));
        if ($res === false) {
            throw new \RuntimeException(sprintf('Could not delete the \'%s\' file.', $key));
        }

        return true;
    }

    /**
     * {@InheritDoc}
     */
    public function rename($key, $new)
    {
        $res = $this->doRequest("RENAME", array("from_key" => $key, "to_key" => $new));
        if ($res === false) {
            throw new \RuntimeException(sprintf('Could not rename the \'%s\' file.', $key));
        }

        return true;
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        return $this->read($key) ? true : false;
    }

    /**
     * {@InheritDoc}
     */
    public function keys()
    {
        throw new \BadMethodCallException("Method not implemented yet.");
    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        throw new \BadMethodCallException("Method not implemented yet.");
    }

    /**
     * {@InheritDoc}
     */
    public function checksum($key)
    {
        throw new \BadMethodCallException("Method not implemented yet.");
    }

    /**
     * {@InheritDoc}
     */
    public function supportsMetadata()
    {
        return false;
    }

    /**
     * Tries to connect MogileFS tracker
     *
     * @return mixed Socket on success, false on failure
     */
    public function connect()
    {
        if ($this->socket) {
            return $this->socket;
        }

        shuffle($this->hosts);

        foreach ($this->hosts as $host) {
            list($ip, $port) = explode(':', $host);
            $this->socket = @fsockopen($ip, $port, $err_no, $err_str, 1);

            if ($this->socket) {
                break;
            }
        }

        if (!$this->socket) {
            return false;
        }

        return $this->socket;
    }

    /**
     * Get available domains and classes from tracker
     *
     * @return mixed Array on success, false on failure
     */
    public function getDomains()
    {
        $res = $this->doRequest('GET_DOMAINS');
        if (!$res) {
            return false;
        }

        $domains = array();

        for ($i = 1; $i <= $res['domains']; $i++) {
            $dom = 'domain' . $i;
            $classes = array();

            // Associate classes to current domain (class name => mindevcount)
            for ($j = 1; $j <= $res[$dom.'classes']; $j++) {
                $classes[$res[$dom . 'class' . $j . 'name']] = $res[$dom . 'class' . $j . 'mindevcount'];
            }

            $domains[] = array('name' => $res[$dom], 'classes' => $classes);
        }

        return $domains;
    }

    /**
     * Close connection to MogileFS tracker
     *
     * @return boolean
     */
    private function close()
    {
        if ($this->socket) {
            return fclose($this->socket);
        }

        return true;
    }

    /**
     * Makes request to MogileFS tracker
     *
     * @param cmd Command
     * @param args Array of arguments
     * @return mixed Array on success, false on failure
     */
    private function doRequest($cmd, $args = array())
    {
        $args['domain'] = $this->domain;
        $params = http_build_query($args);

        if (!$this->connect()) {
            return false;
        }

        fwrite($this->socket, "{$cmd} {$params}\n");
        $line = fgets($this->socket);

        $words = explode(' ', $line);

        if ($words[0] == 'OK') {
            parse_str(trim($words[1]), $result);
        } else {
            $result = false;
            $this->error = join(' ', $words);
        }

        return $result;
    }

    /**
     * Get file location at server from MogileFS tracker
     *
     * @param key File key
     * @return mixed Array on success, false on failure
     */
    private function getPaths($key)
    {
        $res = $this->doRequest("GET_PATHS", array("key" => $key));
        unset($res['paths']);

        return $res;
    }

    /**
     * Sends file to MogileFS tracker
     *
     * @param path Save path at server
     * @param data Data to save
     * @return boolean
     */
    private function putFile($path, $data)
    {
        $info = false;
        $url = parse_url($path);

        $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }

        $buffer = '';
        $b = "\r\n";

        stream_set_blocking($fp, true);
        stream_set_timeout($fp, 30, 200000);

        $out  = "PUT ". $url['path']. " HTTP/1.1". $b;
        $out .= "Host: ". $url['host']. $b;
        $out .= "Content-Length: ". mb_strlen($data). $b. $b;
        $out .= $data;
        $out .= $b. $b;
        fwrite($fp, $out);
        fflush($fp);

        stream_set_blocking($fp, true);
        stream_set_timeout($fp, 30, 200000);

        while (!feof($fp) && !$info['timed_out']) {
            $info = stream_get_meta_data($fp);
            $buffer .= fgets($fp, 128);
        }
        fclose($fp);

        return true;
    }
}
