<?php

declare(strict_types=1);

namespace MDClub\Model;

/**
 * 私信模型
 */
class Inbox extends Abstracts
{
    public $table = 'inbox';
    public $primaryKey = 'inbox_id';
    protected $timestamps = true;

    protected const UPDATE_TIME = false;

    public $columns = [
        'inbox_id',
        'receiver_id',
        'sender_id',
        'content_markdown',
        'content_rendered',
        'create_time',
        'read_time',
    ];
}
