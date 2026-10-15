<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_settings_route_redirects_to_the_account_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertRedirect(route('profile.show', ['tab' => 'account']));
    }

    public function test_profile_page_defaults_to_the_profile_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/me')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('tab', 'profile')
                ->where('mustVerifyEmail', false)
                ->where('status', null)
                ->where('profile.email', $user->email)
                ->where('profile.name', $user->name)
                ->where('profile.postCount', 0)
                ->where('profile.friendCount', 0)
            );
    }

    public function test_account_tab_query_selects_account_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/me?tab=account')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('tab', 'account')
            );
    }

    public function test_unknown_profile_tab_falls_back_to_the_profile_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/me?tab=nope')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Show')
                ->where('tab', 'profile')
            );
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.show', ['tab' => 'account']));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.show', ['tab' => 'account']));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.show', ['tab' => 'account']))
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.show', ['tab' => 'account']));

        $this->assertNotNull($user->fresh());
    }

    public function test_member_can_update_title_department_and_bio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.details.update'), [
                'title' => 'Product Designer',
                'department' => 'Design',
                'bio' => 'I like quiet mornings.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('Product Designer', $user->title);
        $this->assertSame('Design', $user->department);
        $this->assertSame('I like quiet mornings.', $user->bio);
    }

    public function test_profile_counts_come_from_the_database(): void
    {
        $user = User::factory()->create(['bio' => 'Real bio']);
        $friend = User::factory()->create();
        Post::factory()->for($user)->count(2)->create();
        Friendship::factory()->accepted()->create([
            'requester_id' => $user->id,
            'addressee_id' => $friend->id,
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.bio', 'Real bio')
                ->where('profile.postCount', 2)
                ->where('profile.friendCount', 1)
            );
    }

    public function test_title_cannot_exceed_80_characters(): void
    {
        $user = User::factory()->create(['title' => 'Keep me']);

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->patch(route('profile.details.update'), [
                'title' => str_repeat('a', 81),
                'department' => 'Design',
                'bio' => 'Hello',
            ])
            ->assertSessionHasErrors([
                'title' => 'The title field must not be greater than 80 characters.',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('Keep me', $user->refresh()->title);
    }

    public function test_department_cannot_exceed_80_characters(): void
    {
        $user = User::factory()->create(['department' => 'Keep me']);

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->patch(route('profile.details.update'), [
                'title' => 'Designer',
                'department' => str_repeat('a', 81),
                'bio' => 'Hello',
            ])
            ->assertSessionHasErrors([
                'department' => 'The department field must not be greater than 80 characters.',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('Keep me', $user->refresh()->department);
    }

    public function test_bio_cannot_exceed_500_characters(): void
    {
        $user = User::factory()->create(['bio' => 'Keep me']);

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->patch(route('profile.details.update'), [
                'title' => 'Designer',
                'department' => 'Design',
                'bio' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors([
                'bio' => 'The bio field must not be greater than 500 characters.',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertSame('Keep me', $user->refresh()->bio);
    }

    public function test_avatar_must_be_an_image_of_an_allowed_type(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.avatar.store'), [
                'avatar' => UploadedFile::fake()->create('notes.txt', 20, 'text/plain'),
            ])
            ->assertSessionHasErrors('avatar')
            ->assertRedirect(route('profile.show'));

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_avatar_cannot_exceed_2_megabytes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.avatar.store'), [
                'avatar' => UploadedFile::fake()->image('large.jpg')->size(2049),
            ])
            ->assertSessionHasErrors([
                'avatar' => 'The avatar field must not be greater than 2048 kilobytes.',
            ])
            ->assertRedirect(route('profile.show'));

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_member_can_upload_an_avatar_and_replace_the_previous_local_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('avatars/old.jpg', 'old-photo');
        $user->forceFill(['avatar_path' => 'avatars/old.jpg'])->save();

        $this->actingAs($user)
            ->post(route('profile.avatar.store'), [
                'avatar' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.show'));

        $path = $user->refresh()->avatar_path;

        $this->assertIsString($path);
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
    }

    public function test_replacing_a_remote_avatar_does_not_delete_the_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'avatar_path' => 'https://images.example.test/photo.jpg',
        ]);

        $this->actingAs($user)
            ->post(route('profile.avatar.store'), [
                'avatar' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertStringStartsWith('avatars/', (string) $user->refresh()->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_member_can_remove_a_local_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('avatars/mine.jpg', 'photo');
        $user->forceFill(['avatar_path' => 'avatars/mine.jpg'])->save();

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.show'));

        $this->assertNull($user->refresh()->avatar_path);
        Storage::disk('public')->assertMissing('avatars/mine.jpg');
    }

    public function test_removing_a_remote_photo_clears_the_path(): void
    {
        $user = User::factory()->create([
            'avatar_path' => 'https://images.example.test/photo.jpg',
        ]);

        $this->actingAs($user)
            ->delete(route('profile.avatar.destroy'))
            ->assertRedirect(route('profile.show'));

        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_guests_are_redirected_from_profile_updates(): void
    {
        $this->patch(route('profile.details.update'), ['bio' => 'Hi'])->assertRedirect(route('login'));
        $this->post(route('profile.avatar.store'))->assertRedirect(route('login'));
        $this->delete(route('profile.avatar.destroy'))->assertRedirect(route('login'));
        $this->get(route('members.show', User::factory()->create()))->assertRedirect(route('login'));
    }

    public function test_member_can_view_another_profile(): void
    {
        $viewer = User::factory()->create();
        $member = User::factory()->create([
            'name' => 'Jordan Blake',
            'title' => 'Engineering Lead',
            'department' => 'Engineering',
            'bio' => 'I keep the deploys boring.',
        ]);
        Post::factory()->for($member)->create([
            'body' => 'Deploy window is today.',
            'created_at' => now()->subHour(),
        ]);
        Post::factory()->for($member)->create([
            'body' => 'Newest deploy note.',
            'created_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('members.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Show')
                ->where('member.name', 'Jordan Blake')
                ->where('member.title', 'Engineering Lead')
                ->where('member.department', 'Engineering')
                ->where('member.bio', 'I keep the deploys boring.')
                ->where('member.relationship', 'none')
                ->where('posts.0.text', 'Newest deploy note.')
                ->where('posts.1.text', 'Deploy window is today.')
            );
    }

    public function test_viewing_your_own_member_profile_redirects_home_to_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('members.show', $user))
            ->assertRedirect(route('profile.show'));
    }
}
