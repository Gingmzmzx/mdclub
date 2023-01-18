<?php

declare(strict_types=1);

namespace MDClub\Controller\Rss;

use MDClub\Constant\OptionConstant;
use MDClub\Constant\RouteNameConstant;
use MDClub\Facade\Library\Option;
use MDClub\Facade\Library\Request;
use MDClub\Helper\Url;
use MDClub\Initializer\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

/**
 * RSS 控制器抽象类
 */
abstract class Abstracts
{
    protected $siteName;
    protected $language;
    protected $year;
    protected $time;
    protected $copyright;
    protected $cacheTTL;
    protected $feed;

    public function __construct()
    {
        $this->siteName = Option::get(OptionConstant::SITE_NAME);
        $this->language = Option::get(OptionConstant::LANGUAGE);
        $this->year = date('Y');
        $this->time = Request::time();
        $this->copyright = "Copyright {$this->year}, {$this->siteName}";
        $this->cacheTTL = 300;
        $this->feed = new Feed();
    }

    /**
     * 设置成按创建时间倒序排列
     */
    protected function setOrder(): void
    {
        /** @var ServerRequestInterface $request */
        $request = App::$container->get(ServerRequestInterface::class);

        $request = $request->withQueryParams(['order' => '-create_time']);

        App::$container->offsetSet(ServerRequestInterface::class, $request);
    }

    /**
     * 输出 RSS 内容
     *
     * @param string $content
     *
     * @return ResponseInterface
     */
    protected function render(string $content): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = App::$container->get(ResponseInterface::class);

        $response = $response->withHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param string $title
     * @param string $url
     * @param string $feedUrl
     *
     * @return Channel
     */
    protected function getChannel(string $title, string $url, string $feedUrl): Channel
    {
        $channel = new Channel();

        $channel
            ->title($title)
            ->url($url)
            ->feedUrl($feedUrl)
            ->language($this->language)
            ->copyright($this->copyright)
            ->pubDate($this->time)
            ->lastBuildDate($this->time)
            ->ttl($this->cacheTTL)
            ->appendTo($this->feed);

        return $channel;
    }

    /**
     * 输出提问列表 RSS 内容
     *
     * @param array  $questions
     * @param string $title
     * @param string $url
     * @param string $feedUrl
     *
     * @return ResponseInterface
     */
    protected function renderQuestions(
        array $questions,
        string $title,
        string $url,
        string $feedUrl
    ): ResponseInterface {
        $channel = $this->getChannel($title, $url, $feedUrl);

        foreach ($questions as $question) {
            $path = Url::fromRoute(RouteNameConstant::QUESTION, ['question_id' => $question['question_id']]);

            (new Item())
                ->title($question['title'])
                ->description(mb_substr(strip_tags($question['content_rendered']), 0, 80))
                ->contentEncoded($question['content_rendered'])
                ->url($path)
                ->author($question['relationships']['user']['username'])
                ->pubDate($question['create_time'])
                ->guid($path, true)
                ->appendTo($channel);
        }

        $content = $this->feed->render();

        return $this->render($content);
    }

    /**
     * 输出文章列表的 RSS 内容
     *
     * @param array  $articles
     * @param string $title
     * @param string $url
     * @param string $feedUrl
     *
     * @return ResponseInterface
     */
    protected function renderArticles(
        array $articles,
        string $title,
        string $url,
        string $feedUrl
    ): ResponseInterface {
        $channel = $this->getChannel($title, $url, $feedUrl);

        foreach ($articles as $article) {
            $path = Url::fromRoute(RouteNameConstant::ARTICLE, ['article_id' => $article['article_id']]);

            (new Item())
                ->title($article['title'])
                ->description(mb_substr(strip_tags($article['content_rendered']), 0, 80))
                ->contentEncoded($article['content_rendered'])
                ->url($path)
                ->author($article['relationships']['user']['username'])
                ->pubDate($article['create_time'])
                ->guid($path, true)
                ->appendTo($channel);
        }

        $content = $this->feed->render();

        return $this->render($content);
    }

    /**
     * 输出回答列表的 RSS 内容
     *
     * @param array  $answers
     * @param string $title
     * @param string $url
     * @param string $feedUrl
     *
     * @return ResponseInterface
     */
    protected function renderAnswers(
        array $answers,
        string $title,
        string $url,
        string $feedUrl
    ): ResponseInterface {
        $channel = $this->getChannel($title, $url, $feedUrl);

        foreach ($answers as $answer) {
            $path = Url::fromRoute(RouteNameConstant::ANSWER, [
                'question_id' => $answer['question_id'],
                'answer_id' => $answer['answer_id'],
            ]);

            $contentStripTags = strip_tags($answer['content_rendered']);

            (new Item())
                ->title(mb_substr($contentStripTags, 0, 30))
                ->description(mb_substr($contentStripTags, 0, 80))
                ->contentEncoded($answer['content_rendered'])
                ->url($path)
                ->author($answer['relationships']['user']['username'])
                ->pubDate($answer['create_time'])
                ->guid($path, true)
                ->appendTo($channel);
        }

        $content = $this->feed->render();

        return $this->render($content);
    }
}
