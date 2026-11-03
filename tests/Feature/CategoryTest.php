<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_categories(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_category_index_only_lists_own_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::factory()->for($user)->create(['name' => 'Milik Sendiri']);
        Category::factory()->for($otherUser)->create(['name' => 'Milik Orang Lain']);

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Milik Sendiri');
        $response->assertDontSee('Milik Orang Lain');
    }

    public function test_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ff0000',
        ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Makan',
            'type' => 'expense',
            'color' => '#ff0000',
        ]);
    }

    public function test_category_color_must_be_a_valid_hex_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Makan',
            'type' => 'expense',
            'color' => 'bukan-warna',
        ]);

        $response->assertSessionHasErrors('color');
        $this->assertDatabaseMissing('categories', ['name' => 'Makan']);
    }

    public function test_category_name_must_be_unique_per_user_and_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Gaji', 'type' => 'income']);

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Gaji',
            'type' => 'income',
            'color' => '#00ff00',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_category_name_is_allowed_for_a_different_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Bonus', 'type' => 'income']);

        $response = $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Bonus',
            'type' => 'expense',
            'color' => '#00ff00',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'Bonus', 'type' => 'expense']);
    }

    public function test_category_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Lama']);

        $response = $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'Baru',
            'type' => $category->type,
            'color' => '#123456',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Baru']);
    }

    public function test_category_can_be_deleted_by_owner(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_edit_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->get(route('categories.edit', $otherCategory));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->for(User::factory())->create(['name' => 'Tetap']);

        $response = $this->actingAs($user)->put(route('categories.update', $otherCategory), [
            'name' => 'Diubah',
            'type' => $otherCategory->type,
            'color' => '#111111',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id, 'name' => 'Tetap']);
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherCategory = Category::factory()->for(User::factory())->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $otherCategory));

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id]);
    }
}
