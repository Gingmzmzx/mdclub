<?php

declare(strict_types=1);

namespace MDClub\Exception;

use MDClub\Constant\ApiErrorConstant;
use Exception;

/**
 * 字段验证异常
 */
class ValidationException extends Exception implements NeedCaptchaExceptionInterface
{
    /**
     * 下次验证是否需要图形验证码
     *
     * @var bool
     */
    protected $needCaptcha = false;

    /**
     * 错误消息
     *
     * @var array
     */
    protected $errors = [];

    /**
     * @param array $errors       错误字段和错误描述组成的数组
     * @param bool  $needCaptcha  下一次调用该接口是否需要输入图形验证码
     */
    public function __construct(array $errors, bool $needCaptcha = false)
    {
        parent::__construct(...array_reverse(ApiErrorConstant::COMMON_FIELD_VERIFY_FAILED));
        $this->needCaptcha = $needCaptcha;
        $this->errors = $errors;
    }

    /**
     * @inheritDoc
     */
    public function isNeedCaptcha(): bool
    {
        return $this->needCaptcha;
    }

    /**
     * 获取错误信息字段
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
