<?php

declare(strict_types=1);

namespace MDClub\Library;

use Gregwar\Captcha\CaptchaBuilder;
use MDClub\Exception\ValidationException;
use MDClub\Facade\Library\Cache as CacheFacade;
use MDClub\Facade\Library\Throttle as ThrottleFacade;
use MDClub\Facade\Library\Request;
use MDClub\Helper\Str;

/**
 * 图形验证码
 *
 * 1. 调用 generate() 方法生成一个新的图形验证码，返回 token 和 image
 * 2. 前端提交用户输入的验证码 code 和 token，服务端调用 check() 检查 code 和 token 是否匹配
 * 3. 无论是否匹配，每个验证码都只能验证一次，验证后即删除
 */
class Captcha
{
    /**
     * 验证码有效期
     *
     * @var int
     */
    protected $lifeTime = 3600;

    /**
     * 获取缓存键名
     *
     * @param  string $key
     * @return string
     */
    protected function getCacheKey(string $key): string
    {
        return "captcha_{$key}";
    }

    /**
     * 生成图形验证码。每次调用都会生成一个新的验证码。
     *
     * @param  int   $width  验证码图片宽度
     * @param  int   $height 验证码图片高度
     * @return array         [image, token]
     */
    public function generate(int $width = 100, int $height = 36): array
    {
        $builder = new CaptchaBuilder();
        $builder->build($width, $height);

        $token = Str::guid();
        $code = $builder->getPhrase();
        $cacheKey = $this->getCacheKey($token);

        CacheFacade::set($cacheKey, $code, $this->lifeTime);

        return [
            'image' => $builder->inline(),
            'token' => $token,
        ];
    }

    /**
     * 检查验证码是否正确，无论正确与否，检查过后直接删除记录
     *
     * @param  string $token 验证码请求token
     * @param  string $code  验证码字符
     * @return bool
     */
    public function check(string $token, string $code): bool
    {
        if (!$token || !$code) {
            return false;
        }

        $cacheKey = $this->getCacheKey($token);
        $correctCode = CacheFacade::get($cacheKey);

        if (!$correctCode) {
            return false;
        }

        CacheFacade::delete($cacheKey);

        return strtolower($correctCode) === strtolower($code);
    }

    /**
     * 下一次请求是否需要验证码
     *
     * @param  string $id        区别用户身份的字符串
     * @param  string $action    操作名称
     * @param  int    $max_count 最多操作次数
     * @param  int    $period    在该时间范围内
     * @return bool
     * @throws ValidationException
     */
    public function isNextTimeNeed(string $id, string $action, int $max_count, int $period): bool
    {
        $parsedBody = Request::getParsedBody();

        $remaining = ThrottleFacade::getActLimit($id, $action, $max_count, $period);
        $needCaptcha = $remaining <= 1;
        $captchaToken = $parsedBody['captcha_token'] ?? '';
        $captchaCode = $parsedBody['captcha_code'] ?? '';

        if ($remaining <= 0 && !$this->check($captchaToken, $captchaCode)) {
            $errors = [ 'captcha_code' => '图形验证码错误' ];

            throw new ValidationException($errors, $needCaptcha);
        }

        return $needCaptcha;
    }
}
