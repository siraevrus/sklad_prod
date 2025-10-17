<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyArchiveApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Тест: Архивирование компании успешно
     */
    public function test_can_archive_company(): void
    {
        $user = User::first();
        $company = Company::first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/archive");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'is_archived',
                    'archived_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно архивирована',
            ]);

        // Проверяем в БД
        $this->assertTrue($company->fresh()->is_archived);
        $this->assertNotNull($company->fresh()->archived_at);
    }

    /**
     * Тест: Восстановление компании из архива
     */
    public function test_can_restore_company(): void
    {
        $user = User::first();
        $company = Company::create([
            'name' => 'Компания для восстановления',
            'email' => 'restore-test-'.time().'@example.com',
            'inn' => str_pad(rand(1000000000, 9999999999), 12, '0', STR_PAD_LEFT),
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/restore");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'is_archived',
                    'archived_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Компания успешно восстановлена',
            ]);

        // Проверяем в БД
        $this->assertFalse($company->fresh()->is_archived);
        $this->assertNull($company->fresh()->archived_at);
    }

    /**
     * Тест: Ошибка при архивации уже архивированной компании
     */
    public function test_cannot_archive_already_archived_company(): void
    {
        $user = User::first();
        $company = Company::create([
            'name' => 'Уже архивированная компания',
            'email' => 'already-archived-'.time().'@example.com',
            'inn' => str_pad(rand(1000000000, 9999999999), 12, '0', STR_PAD_LEFT),
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/archive");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Компания уже архивирована',
            ]);
    }

    /**
     * Тест: Ошибка при восстановлении неархивированной компании
     */
    public function test_cannot_restore_non_archived_company(): void
    {
        $user = User::first();
        $company = Company::create([
            'name' => 'Неархивированная компания',
            'email' => 'not-archived-'.time().'@example.com',
            'inn' => str_pad(rand(1000000000, 9999999999), 12, '0', STR_PAD_LEFT),
            'is_archived' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/restore");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Компания не архивирована',
            ]);
    }

    /**
     * Тест: Архивированная компания не показывается в списке
     */
    public function test_archived_company_not_in_list(): void
    {
        $user = User::first();
        $company = Company::create([
            'name' => 'Компания для списка',
            'email' => 'list-test-'.time().'@example.com',
            'inn' => str_pad(rand(1000000000, 9999999999), 12, '0', STR_PAD_LEFT),
            'is_archived' => false,
        ]);

        // Проверяем, что компания в списке
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/companies');

        $this->assertContains($company->id, $response->json('data.*.id'));

        // Архивируем компанию
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/companies/{$company->id}/archive");

        // Проверяем, что компания больше не в списке
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/companies');

        $this->assertNotContains($company->id, $response->json('data.*.id'));
    }

    /**
     * Тест: Получение архивированной компании возвращает 404
     */
    public function test_cannot_get_archived_company(): void
    {
        $user = User::first();
        $company = Company::create([
            'name' => 'Компания для получения',
            'email' => 'get-archived-'.time().'@example.com',
            'inn' => str_pad(rand(1000000000, 9999999999), 12, '0', STR_PAD_LEFT),
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/companies/{$company->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Компания не найдена',
            ]);
    }
}
