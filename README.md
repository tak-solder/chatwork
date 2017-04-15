# Chatwork Api Client for PHP

Chatwork API(v2)にPHPからアクセスするためのクライアントです。
v1からv2へのバージョンアップに伴い、返り値内のmessage_idがint型からstring型に変更になっていますので、ご注意ください。

## インストール

composer経由でインストールすることができます。

```
composer require taksolder/chatwork
```

## 使い方

インスタンス生成時にAPIトークンを指定してください。

```php
$chatwork = new \TakSolder\Chatwork\Chatwork('YOUR_CHATWORK_API_TOKEN');

$chatwork->me(); // https://api.chatwork.com/v1/me
```
## v1を使用する場合

2017年5月上旬で停止予定ですが、v1を使用する場合はエンドポイントを設定することで利用可能です。

```php
$endpoint = 'https://api.chatwork.com/v1';
$chatwork = new Chatwork('YOUR_CHATWORK_API_TOKEN', $endpoint);

$chatwork->me(); // https://api.chatwork.com/v1/me
```

## その他

近々、大幅アップデートを検討中です
