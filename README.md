# Chatwork Api Client for PHP

Chatwork API(v1)にPHPからアクセスするためのクライアントです。

## インストール

composer経由でインストールすることができます。

```
composer install taksolder/chatwork
```

## 使い方

インスタンス生成時にAPIトークンを指定してください。

```php
$chatwork = api = new Chatwork('YOUR_CHATWORK_API_TOKEN');

$chatwork->me(); // https://api.chatwork.com/v1/me
```
