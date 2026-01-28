<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class LeaveRoomTest extends TestCase
{
    public function test_it_allows_leaving_a_room(): void
    {
        $room_key = (string) Str::uuid();
        $user_key = (string) Str::uuid();

        $response = $this->postJson("/api/chat/rooms/{$room_key}/leave", [
            'user_key' => $user_key,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Left the room successfully.',
            ]);
    }
}
