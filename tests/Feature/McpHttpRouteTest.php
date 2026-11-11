<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpHttpRouteTest extends TestCase
{
    use RefreshDatabase;

    private function initializeBody(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => [],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ];
    }

    public function test_mcp_endpoint_rejects_requests_without_a_token(): void
    {
        $this->postJson('/mcp/courses', $this->initializeBody(), ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_mcp_endpoint_accepts_a_valid_sanctum_token(): void
    {
        $group = Group::create(['name' => 'Kurzusmenedzser']);
        $permission = Permission::create(['name' => 'manage-courses', 'description' => 'x']);
        $group->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->groups()->attach($group);

        $token = $user->createToken('mcp-course-editor')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/mcp/courses', $this->initializeBody(), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('result.protocolVersion', '2025-03-26');
    }
}
