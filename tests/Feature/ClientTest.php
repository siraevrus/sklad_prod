
public function test_client_creation_with_new_fields()
{
    $clientData = [
        'name' => 'Test Client',
        'email' => 'test@example.com',
        'address' => '123 Test Street',
        'currency_rate' => 1.23,
    ];
    $response = $this->postJson('/api/clients', $clientData);
    $response->assertStatus(201);
    $response->assertJson($clientData);
}

