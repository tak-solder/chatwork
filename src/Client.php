<?php


namespace TakSolder\Chatwork;

/**
 * APIに接続するためのクライアント
 * Class Client
 * @package TakSolder\Chatwork
 */
class Client
{
    const METHOD_GET = 1;
    const METHOD_POST = 2;
    const METHOD_PUT = 3;
    const METHOD_DELETE = 4;

    /**
     * curlリソース
     * @var resource
     */
    private $curl;

    /**
     * エンドポイントのベースURL
     * @var string
     */
    private $baseUri = 'https://api.chatwork.com/v1';

    private $limit = [
        'limit' => -1,
        'remaining' => -1,
        'reset' => -1
    ];

    public function __construct($token, $baseUri = null)
    {
        $this->curl = curl_init();

        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_HTTPHEADER, [
            'X-ChatWorkToken: ' . $token
        ]);

        if ($baseUri) {
            $this->baseUri = $baseUri;
        }
    }

    /**
     * curl_setoptのラッパー
     * @param  int $option
     * @param  mixed   $value
     * @return mixed
     */
    private function setOption($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * APIと通信
     * @param $path
     * @param array $data
     * @param int $methodType
     *
     * @return array
     * @throws ChatworkException
     */
    public function request($path, array $data = [], $methodType = self::METHOD_GET)
    {
        $method = $this->getRequestMethod($methodType);
        $uri = $this->baseUri . $path;

        if (!empty($data)) {
            $query = http_build_query($data, null, '&', PHP_QUERY_RFC3986);
            if ($method === 'GET') {
                $uri .= '?' . $query;
            } else {
                $this->setOption(CURLOPT_POSTFIELDS, $query);
            }
        }

        $this->setOption(CURLOPT_URL, $uri);
        $this->setOption(CURLOPT_CUSTOMREQUEST, $method);

        $response = $this->exec();

        if (!$response && !is_array($response)) {
            throw new ChatworkException('request error');
        }

        if (isset($response['errors'])) {
            throw new ChatworkException(implode(', ', $response['errors']));
        }

        return $response;
    }

    /**
     * リクエスト実行
     * @return array
     */
    private function exec()
    {
        $response = curl_exec($this->curl);
        $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $this->setRateLimit($header);

        $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if ($code == 204) {
            return []; //contents empty
        }

        return json_decode(substr($response, $headerSize), true);
    }

    /**
     * レスポンスヘッダーから接続制限の情報を更新
     * @param $header
     */
    private function setRateLimit($header)
    {
        preg_match_all('/X-RateLimit-(\w+):\s*(\d+)/', $header, $m);
        array_map(function ($key, $value) {
            $key = strtolower($key);
            $this->limit[$key] = intval($value);
        }, $m[1], $m[2]);
    }

    /**
     * 接続制限の情報を取得
     * @param null $name
     * @return array|int|mixed
     */
    public function getRequestLimit($name = null)
    {
        if(!$name){
            return $this->limit;
        }

        if(!isset($this->limit[$name])){
            return -1;
        }

        return $this->limit[$name];
    }

    /**
     * メソッドの判定
     * @param int $method
     * @return string
     */
    private function getRequestMethod($method)
    {
        if(is_string($method)){
            return $method;
        }

        switch ($method) {
            case self::METHOD_POST:
                return 'POST';
            case self::METHOD_DELETE:
                return 'DELETE';
            case self::METHOD_PUT;
                return 'PUT';
        }

        return 'GET';
    }

}