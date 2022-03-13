<?php

/**
 * @desc 阿里云OSS适配器
 *
 * @author Tinywan(ShaoBo Wan)
 * @date 2022/3/7 19:54
 */

declare(strict_types=1);

namespace Tinywan\Storage\Adapter;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Throwable;
use Tinywan\Storage\Exception\StorageException;

class QiniuAdapter extends AdapterAbstract
{
    protected static $instance = null;

    /**
     * @desc: 实例
     */
    public static function getInstance(): ?UploadManager
    {
        if (is_null(self::$instance)) {
            static::$instance = new UploadManager();
        }

        return static::$instance;
    }

    public static function getUploadToken()
    {
        $config = config('plugin.tinywan.storage.app.storage.qiniu');
        $auth = new Auth($config['accessKey'], $config['secretKey']);

        return $auth->uploadToken($config['bucket']);
    }

    /**
     * @desc: 方法描述
     *
     * @author Tinywan(ShaoBo Wan)
     */
    public function uploadFile(array $options = []): array
    {
        try {
            $config = config('plugin.tinywan.storage.app.storage.qiniu');
            $result = [];
            foreach ($this->files as $key => $file) {
                $uniqueId = hash_file('md5', $file->getPathname());
                $saveName = $uniqueId.'.'.$file->getUploadExtension();
                $object = $config['dirname'].DIRECTORY_SEPARATOR.$saveName;
                $temp = [
                    'key' => $key,
                    'origin_name' => $file->getUploadName(),
                    'save_name' => $saveName,
                    'save_path' => $object,
                    'url' => $config['domain'].DIRECTORY_SEPARATOR.$object,
                    'unique_id' => $uniqueId,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getUploadMineType(),
                    'extension' => $file->getUploadExtension(),
                ];
                list($ret, $err) = self::getInstance()->putFile(self::getUploadToken(), $object, $file->getPathname());
                if (!empty($err)) {
                    throw new StorageException((string) $err);
                }
                array_push($result, $temp);
            }
        } catch (Throwable $exception) {
            throw new StorageException($exception->getMessage());
        }

        return $result;
    }
}