<?php

namespace App\Enums;

/**
 * 聊天配對狀態
 */
enum ChatMatchState: string
{
    /**
     * 閒置
     */
    case Idle = 'idle';

    /**
     * 等待配對
     */
    case Queue = 'queue';

    /**
     * 已進入房間
     */
    case Room = 'room';
}
