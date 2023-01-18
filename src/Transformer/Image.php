<?php

declare(strict_types=1);

namespace MDClub\Transformer;

use MDClub\Facade\Model\ImageModel;
use MDClub\Facade\Service\ImageService;

/**
 * 图片转换器
 */
class Image extends Abstracts
{
    protected $table = 'image';
    protected $primaryKey = 'key';
    protected $availableIncludes = ['user', 'question', 'article', 'answer'];

    /**
     * 格式化图片信息
     *
     * @param  array $item
     * @return array
     */
    protected function format(array $item): array
    {
        if (isset($item['key'], $item['create_time'])) {
            $item['urls'] = ImageService::getUrls($item['key'], $item['create_time']);
        }

        if (isset($item['filename'])) {
            $item['filename'] = htmlspecialchars($item['filename']);
        }

        // 接口的 url 不能以图片后缀结尾，所以把图片的 key 中的 . 替换为 _
        if (isset($item['key'])) {
            $item['key'] = str_replace('.', '_', $item['key']);
        }

        return $item;
    }

    /**
     * 处理 question, article, answer 子资源
     *
     * @param  array  $items
     * @param  string $itemType
     * @return array
     */
    private function handle(array $items, string $itemType): array
    {
        $ids = [];

        foreach ($items as $item) {
            if ($item['item_type'] === $itemType && !in_array($item['item_id'], $ids)) {
                $ids[] = $item['item_id'];
            }
        }

        $targets = $this->{$itemType . 'Transformer'}->getInRelationship($ids);

        foreach ($items as &$item) {
            if ($item['item_type'] === $itemType) {
                $item['relationship'][$itemType] = $targets[$item['item_id']];
            }
        }

        return $items;
    }

    /**
     * 添加 question 子资源
     *
     * @param  array $items
     * @return array
     */
    protected function question(array $items): array
    {
        return $this->handle($items, 'question');
    }

    /**
     * 添加 article 子资源
     *
     * @param  array $items
     * @return array
     */
    protected function article(array $items): array
    {
        return $this->handle($items, 'article');
    }

    /**
     * 添加 answer 子资源
     *
     * @param  array $items
     * @return array
     */
    protected function answer(array $items): array
    {
        return $this->handle($items, 'answer');
    }

    /**
     * 获取 image 子资源
     *
     * @param array $keys
     * @return array
     */
    public function getInRelationship(array $keys): array
    {
        if (!$keys) {
            return [];
        }

        $images = ImageModel::select($keys);

        return collect($images)
            ->keyBy('key')
            ->map(function ($item) {
                $item['urls'] = ImageService::getUrls($item['key'], $item['create_time']);
                $item['filename'] = htmlspecialchars($item['filename']);

                return $item;
            })
            ->unionFill($keys)
            ->all();
    }
}
