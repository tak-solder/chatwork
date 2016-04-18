<?php


namespace TakSolder\Chatwork;

/**
 * ChatworkAPIの処理
 * Class Chatwork
 * @package TakSolder\Chatwork
 */
class Chatwork
{
    const MEMBER_READONLY = 1;
    const MEMBER_NORMAL = 2;
    const MEMBER_ADMIN = 3;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client|string $token
     * @param string $baseUri
     */
    public function __construct($token, $baseUri = null)
    {
        if ($token instanceof Client) {
            $this->client = $token;
        } else {
            $this->client = new Client($token, $baseUri);
        }
    }

    /**
     * APIの接続制限関連の情報を取得
     * @param string|null $name
     * @return array|int
     */
    public function getRequestLimit($name = null)
    {
        return $this->client->getRequestLimit($name);
    }

    /**
     * クライアントを返す
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * 自身のプロフィール情報を取得
     * @return array
     * @throws ChatworkException
     */
    public function me()
    {
        $path = '/me';
        return $this->client->request($path);
    }

    /**
     * 未読、未読To数、未完了タスク数を取得
     * @return array
     * @throws ChatworkException
     */
    public function myStatus()
    {
        $path = '/my/status';
        return $this->client->request($path);
    }

    /**
     * タスク一覧を取得
     * @param bool|null $isDone
     * @param int|null $clientId
     * @return array
     * @throws ChatworkException
     */
    public function myTasks($isDone = null, $clientId = null)
    {
        $attributes = [];
        if (!is_null($isDone)) {
            $attributes['status'] = $isDone ? 'done' : 'open';
        }
        if ($clientId) {
            $attributes['assigned_by_account_id'] = intval($clientId);
        }

        $path = '/my/tasks';
        return $this->client->request($path, $attributes);
    }

    /**
     * コンタクト一覧を取得
     * @return array
     * @throws ChatworkException
     */
    public function contacts()
    {
        $path = '/contacts';
        return $this->client->request($path);
    }

    /**
     * チャット一覧の取得
     * @return array
     * @throws ChatworkException
     */
    public function roomList()
    {
        $path = '/rooms';
        return $this->client->request($path);
    }

    /**
     * チャットを新規作成
     * @param array $attributes
     * @return array
     * @throws ChatworkException
     */
    public function createRoom(array $attributes)
    {
        $path = '/rooms';
        return $this->client->request($path, $attributes, Client::METHOD_POST);
    }

    /**
     * チャットの情報を取得
     * @param int $roomId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomInfo($roomId)
    {
        $path = '/rooms/' . $roomId;
        return $this->client->request($path);
    }

    /**
     * チャットの権限を取得
     * @param int $roomId
     * @return int
     * @throws ChatworkException
     */
    public function getRoomRole($roomId)
    {
        try {
            $info = $this->getRoomInfo($roomId);
        } catch (ChatworkException $e) {
            if ($e->getMessage() !== 'You don\'t have permission to get this room') {
                throw $e;
            }

            return 0;
        }

        switch ($info['role']) {
            case 'admin':
                return self::MEMBER_ADMIN;
            case 'member':
                return self::MEMBER_NORMAL;
            case 'readonly':
                return self::MEMBER_READONLY;
        }

        return 0;
    }

    /**
     * チャットルームに含まれているかの確認
     * @param int $roomId
     * @return bool
     * @throws ChatworkException
     */
    public function isRoomMember($roomId)
    {
        return $this->getRoomRole($roomId) !== 0;
    }

    /**
     * チャット情報を編集(メンバー設定を除く)
     * @param int $roomId
     * @param array $attributes
     * @return array
     * @throws ChatworkException
     */
    public function updateRoomInfo($roomId, array $attributes)
    {
        $path = '/rooms/' . $roomId;
        return $this->client->request($path, $attributes, Client::METHOD_PUT);
    }

    /**
     * チャットから退出
     * @param int $roomId
     * @throws ChatworkException
     */
    public function leaveRoom($roomId)
    {
        $path = '/rooms/' . $roomId;
        $this->client->request($path, ['action_type' => 'leave'], Client::METHOD_DELETE);
    }

    /**
     * チャットを削除
     * @param int $roomId
     * @throws ChatworkException
     */
    public function deleteRoom($roomId)
    {
        $path = '/rooms/' . $roomId;
        $this->client->request($path, ['action_type' => 'delete'], Client::METHOD_DELETE);
    }

    /**
     * チャットのメンバー一覧を取得
     * @param int $roomId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomMembers($roomId)
    {
        $path = '/rooms/' . $roomId.'/members';
        return $this->client->request($path);
    }

    /**
     * チャットのメンバーの権限とIDの一覧を取得
     * @param int $roomId
     * @return array
     */
    public function getRoomMemberList($roomId)
    {
        $list = [
            'admin' => [],
            'member' => [],
            'readonly' => []
        ];

        $members = $this->getRoomMembers($roomId);
        foreach ($members as $member) {
            $list[$member['role']] = $member['account_id'];
        }

        return $list;
    }


    /**
     * チャットのメンバー/権限を更新
     * @param int $roomId
     * @param array $attributes
     * @return array
     * @throws ChatworkException
     */
    public function updateRoomMembers($roomId, array $attributes)
    {
        $path = '/rooms/' . $roomId . '/members';
        return $this->client->request($path, $attributes, Client::METHOD_PUT);
    }

    /**
     * チャットの直近のメッセージを取得
     * @param int $roomId
     * @param bool $unreadOnly
     * @return array
     * @throws ChatworkException
     */
    public function getRoomMessages($roomId, $unreadOnly = true)
    {
        $path = '/rooms/' . $roomId . '/messages';
        return $this->client->request($path, ['force' => intval($unreadOnly)]);
    }

    /**
     * チャットにメッセージを追加
     * @param int $roomId
     * @param int $message
     * @return array
     * @throws ChatworkException
     */
    public function addRoomMessages($roomId, $message)
    {
        $path = '/rooms/' . $roomId . '/messages';
        return $this->client->request($path, ['body' => $message], Client::METHOD_POST);
    }

    /**
     * 指定したIDのメッセージを取得
     * @param int $roomId
     * @param int $messageId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomMessage($roomId, $messageId)
    {
        $path = '/rooms/' . $roomId . '/messages/'.$messageId;
        return $this->client->request($path);
    }

    /**
     * チャットのタスク一覧を取得
     * @param int $roomId
     * @param int|null $isDone
     * @param int|null $workerId
     * @param int|null $clientId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomTasks($roomId, $isDone = null, $workerId = null, $clientId = null)
    {
        $attributes = [];
        if (!is_null($isDone)) {
            $attributes['status'] = $isDone;
        }
        if ($workerId) {
            $attributes['account_id'] = $workerId;
        }
        if ($clientId) {
            $attributes['assigned_by_account_id'] = $clientId;
        }

        $path = '/rooms/' . $roomId . '/tasks';
        return $this->client->request($path, $attributes);
    }

    /**
     * チャットにタスクを追加
     * @param int $roomId
     * @param string $subject
     * @param array $workerIds
     * @param null|int $limit
     * @return array
     * @throws ChatworkException
     */
    public function addRoomTasks($roomId, $subject, array $workerIds, $limit = null)
    {
        $attributes = [
            'body' => $subject,
            'to_ids' => implode(',', $workerIds),
        ];
        if ($limit) {
            $attributes['limit'] = $limit;
        }

        $path = '/rooms/' . $roomId . '/tasks';
        return $this->client->request($path, $attributes, Client::METHOD_POST);
    }

    /**
     * 指定したIDのタスクを取得
     * @param int $roomId
     * @param int $taskId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomTask($roomId, $taskId)
    {
        $path = '/rooms/' . $roomId . '/tasks/' . $taskId;
        return $this->client->request($path);
    }

    /**
     * チャットのファイル一覧を取得
     * @param int $roomId
     * @param int|null $ownerId
     * @return array
     * @throws ChatworkException
     */
    public function getRoomFiles($roomId, $ownerId = null)
    {
        $attributes = [];
        if ($ownerId) {
            $attributes['account_id'] = $ownerId;
        }
        $path = '/rooms/' . $roomId . '/files';
        return $this->client->request($path, $attributes);
    }

    /**
     * 指定したファイルの情報とダウンロードリンクを取得
     * @param int $roomId
     * @param int $fileId
     * @param bool $downloadLink
     * @return array
     * @throws ChatworkException
     */
    public function getRoomFile($roomId, $fileId, $downloadLink = true)
    {
        $attributes = [
            'create_download_url' => $downloadLink
        ];
        $path = '/rooms/' . $roomId . '/files/' . $fileId;
        return $this->client->request($path, $attributes);
    }
}