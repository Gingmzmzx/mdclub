<?php

declare(strict_types=1);

namespace MDClub\Controller\RestApi;

use MDClub\Controller\RestApi\Traits\Getable;
use MDClub\Facade\Library\Request;
use MDClub\Facade\Service\ImageService;

/**
 * 图片 API
 */
class Image extends Abstracts
{
    use Getable;

    /**
     * @inheritDoc
     */
    protected function getService(): string
    {
        return \MDClub\Service\Image::class;
    }

    /**
     * 上传单张图片
     *
     * @return array
     */
    public function upload(): array
    {
        $files = Request::getUploadedFiles();
        $key = ImageService::upload($files);

        return ImageService::get($key);
    }

    /**
     * 更新图片信息
     *
     * @param  string   $key
     * @return array
     */
    public function update(string $key): array
    {
        $requestBody = Request::getParsedBody();
        ImageService::update($key, $requestBody);

        return ImageService::get($key);
    }

    /**
     * 批量删除图片
     *
     * @param array $keys
     *
     * @return null
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as &$key) {
            $key = str_replace('_', '.', $key);
        }

        ImageService::deleteMultiple($keys);

        return null;
    }

    /**
     * 删除图片
     *
     * @param  string   $key
     * @return null
     */
    public function delete(string $key)
    {
        $key = str_replace('_', '.', $key);

        ImageService::delete($key);

        return null;
    }
}
