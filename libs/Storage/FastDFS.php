<?php
/**
 * Description of FastDFS.php.
 *
 * @package Kinone\Bundle\Storage
 * @author zhenhao<hit_zhenhao@163.com>
 */
namespace Kinone\Bundle\Storage;

class FastDFS
{

    private $_fdfs;

    private $_tracker_index;
    private $_domain;
    private $_group;

    private $_options = array();

    public function __construct($config = array())
    {
        $this->parseConfig($config);
        $this->_fdfs = new \FastDFS($this->_tracker_index);
    }

    /**
     * 上传一个文件到Fastdfs
     *
     * @param string $file_name 上传的文件地址
     * @param string $ext_name 上传的文件扩展名
     * @param array $meta 文件附加属性
     *
     * @return string|boolean file id (with group name)
     */
    public function put($file_name, $ext_name = null, $meta = array())
    {
        return $this->_fdfs->storage_upload_by_filename1($file_name, $ext_name, $meta);
    }

    /**
     * 将图片转成jpge上传至Fastdfs.
     *
     * @param resource $image
     * @return string
     */
    public function putImage($image)
    {
        $buff = $this->getImageData($image);
        return $this->putBuff($buff, 'jpg');
    }

    /**
     * 将slave图片转成jpge上传至Fastdfs.
     * @param $image
     * @param $fid
     * @param $suffix
     * @return bool|string
     */
    public function putSlaveImage($image, $fid, $suffix)
    {
        $buff = $this->getImageData($image);
        return $this->putSlaveBuff($buff, $fid, $suffix);
    }

    /**
     * 上传一个 slave 文件到Fastdfs
     *
     * @param string $file_name 上传的文件地址
     * @param string $master_file_id 文件id
     * @param string $suffix 后缀名
     *
     * @return string|boolean slave file id (with group name)
     */
    public function putSlave($file_name, $master_file_id, $suffix)
    {
        return $this->_fdfs->storage_upload_slave_by_filename1($file_name, $master_file_id, $suffix);
    }

    /**
     * 直接上传文件内容至服务器
     *
     * @param string $file_buff
     * @param string $file_ext_name //后缀名称
     * @return string file_id for success, false for error
     */
    public function putBuff($file_buff, $file_ext_name)
    {
        return $this->_fdfs->storage_upload_by_filebuff1($file_buff, $file_ext_name);
    }

    /**
     * 直接上传 slave 文件内容至服务器
     *
     * @param string $file_buff
     * @param $master_file_id
     * @param $suffix
     * @return bool|string slave file id (with group name)
     */
    public function putSlaveBuff($file_buff, $master_file_id, $suffix)
    {
        return $this->_fdfs->storage_upload_slave_by_filebuff1($file_buff, $master_file_id, $suffix);
    }

    /**
     * 根据文件 id 获取文件内容
     *
     * @param string $file_id 文件 id
     * @param string $suffix 不为空则为 slave 文件后缀
     * @return string|boolean
     */
    public function get($file_id, $suffix = '')
    {
        if (!empty($suffix))
        {
            $file_id = $this->getSlaveName($file_id, $suffix);
        }
        return $this->_fdfs->storage_download_file_to_buff1($file_id);
    }

    /**
     * 根据文件 id 下载文件
     *
     * @param string $file_id 文件 id
     * @param string $local_file_name 本地存的文件名
     * @param string $suffix 不为空则为 slave 文件后缀
     * @return boolean
     */
    public function download($file_id, $local_file_name, $suffix = '')
    {
        if (!empty($suffix))
        {
            $file_id = $this->getSlaveName($file_id, $suffix);
        }
        return $this->_fdfs->storage_download_file_to_file1($file_id, $local_file_name);
    }

    /**
     * 获取 file meta
     *
     * @param string $file_id
     * @param array $meta
     * @return boolean
     */
    public function setMeta($file_id, $meta)
    {
        return $this->_fdfs->storage_set_metadata1($file_id, $meta);
    }

    /**
     * 获取 file meta
     *
     * @param string $file_id
     * @return array file meta
     */
    public function getMeta($file_id)
    {
        return $this->_fdfs->storage_get_metadata1($file_id);
    }

    /**
     * 获取 file info
     *
     * @param string $file_id
     * @return array file info
     */
    public function getInfo($file_id)
    {
        return $this->_fdfs->get_file_info1($file_id);
    }

    /**
     * 删除一个文件及其slave
     *
     * @param string $file_id
     * @param boolean $delete_slave 是否删除从文件
     * @param array $suffixs
     * @return bool
     */
    public function delete($file_id, $delete_slave = false, $suffixs = array())
    {
        if ($delete_slave === true)
        {
            foreach ($suffixs as $suffix)
            {
                $this->_fdfs->storage_delete_file1($this->getSlaveName($file_id, $suffix));
            }
        }

        return $this->_fdfs->storage_delete_file1($file_id);
    }

    /**
     * 获取 slave file name/id
     *
     * @param string $file_id master name/id
     * @param string $suffix slave 后缀
     * @return string slave file name/id
     */
    public function getSlaveName($file_id, $suffix)
    {
        return $this->_fdfs->gen_slave_filename($file_id, $suffix);
    }

    /**
     * 获取 file 的url path (移除group name)
     *
     * @param string $file_id master name/id
     * @param string $suffix slave 后缀
     * @return string
     */
    public function getUrlPath($file_id, $suffix = '')
    {
        if (!empty($suffix))
        {
            $file_id = $this->getSlaveName($file_id, $suffix);
        }
        return substr($file_id, strpos($file_id, '/'));
    }

    /**
     * 获取 file 的url地址
     *
     * @param string $file_id master name/id
     * @param string $suffix slave 后缀
     * @return string
     */
    public function getUrl($file_id, $suffix = '')
    {
        $filepath = $this->getUrlPath($file_id, $suffix);
        if (empty($filepath))
        {
            return false;
        }
        if (isset($this->_options['url_with_group']) && $this->_options['url_with_group'] === false)
        {
            return $this->_domain . $filepath;
        }
        return $this->_domain . '/' . $this->_group . $filepath;
    }

    public function getFileId($url)
    {
        $file_id = ltrim(ltrim($url, $this->_domain), '/');
        if (isset($this->_options['url_with_group']) && $this->_options['url_with_group'] === false)
        {
            $file_id = $this->_group . '/' . $file_id;
        }

        return $file_id;
    }

    /**
     * 根据file_id 判断文件是否存在
     *
     * @param string $file_id master name/id
     * @param string $suffix
     * @return string true for exist, false for not exist
     */
    public function isExist($file_id, $suffix = '')
    {
        if (!empty($suffix))
        {
            $file_id = $this->getSlaveName($file_id, $suffix);
        }
        return $this->_fdfs->storage_file_exist1($file_id);
    }

    public function getError()
    {
        return $this->_fdfs->get_last_error_no() . ':' . $this->_fdfs->get_last_error_info();
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->_fdfs, $name), $args);
    }

    /**
     * Initialize adapter object
     * @param array $config
     * @return array
     */
    public function parseConfig($config)
    {
        $default = array(
            'tracker_index' => 0,
            'domain' => '',
            'group' => '',
            'options' => array(),
        );

        $config = array_merge($default, $config);
        $this->_tracker_index = $config['tracker_index'];
        $this->_domain = $config['domain'];
        $this->_group = $config['group'];
        $this->_options = $config['options'];

        return $config;
    }

    public function __destruct()
    {
        $this->_fdfs->tracker_close_all_connections();
    }

    private function getImageData($image)
    {
        ob_start();
        imagejpeg($image);
        return ob_get_clean();
    }
}