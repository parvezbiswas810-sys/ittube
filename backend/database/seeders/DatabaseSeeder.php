<?php

namespace Database\Seeders;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\HomeSection;
use App\Services\SettingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $this->seedSettings();
        $this->seedCategories();
        $this->seedHomeSections();
    }

    protected function seedRolesAndPermissions(): void
    {
        $permissions = [
            ['name' => 'View dashboard', 'slug' => 'dashboard.view', 'group' => 'general'],
            ['name' => 'Manage users', 'slug' => 'users.manage', 'group' => 'moderation'],
            ['name' => 'Manage channels', 'slug' => 'channels.manage', 'group' => 'moderation'],
            ['name' => 'Manage videos', 'slug' => 'videos.manage', 'group' => 'content'],
            ['name' => 'Manage shorts', 'slug' => 'shorts.manage', 'group' => 'content'],
            ['name' => 'Manage categories', 'slug' => 'categories.manage', 'group' => 'content'],
            ['name' => 'Manage comments', 'slug' => 'comments.manage', 'group' => 'moderation'],
            ['name' => 'Manage reports', 'slug' => 'reports.manage', 'group' => 'moderation'],
            ['name' => 'Manage notifications', 'slug' => 'notifications.manage', 'group' => 'content'],
            ['name' => 'Manage home page', 'slug' => 'home.manage', 'group' => 'content'],
            ['name' => 'Manage settings', 'slug' => 'settings.manage', 'group' => 'system'],
            ['name' => 'Manage admin roles', 'slug' => 'roles.manage', 'group' => 'system'],
            ['name' => 'View activity logs', 'slug' => 'logs.view', 'group' => 'system'],
            ['name' => 'Manage authentication', 'slug' => 'auth.manage', 'group' => 'system'],
            ['name' => 'Manage copyright', 'slug' => 'copyright.manage', 'group' => 'moderation'],
            ['name' => 'Manage live streams', 'slug' => 'live.manage', 'group' => 'content'],
            ['name' => 'Manage platform integrations', 'slug' => 'platform.manage', 'group' => 'system'],
        ];

        foreach ($permissions as $permission) {
            AdminPermission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $super = AdminRole::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'is_super' => true,
            'description' => 'Full access to every admin area.',
        ]);

        $moderator = AdminRole::firstOrCreate(['slug' => 'moderator'], [
            'name' => 'Moderator',
            'is_super' => false,
            'description' => 'Reviews reports and moderates content.',
        ]);

        $support = AdminRole::firstOrCreate(['slug' => 'support-admin'], [
            'name' => 'Support Admin',
            'is_super' => false,
            'description' => 'Handles users and support requests.',
        ]);

        $ids = fn (array $slugs) => AdminPermission::whereIn('slug', $slugs)->pluck('id')->all();

        $moderator->permissions()->sync($ids([
            'dashboard.view', 'videos.manage', 'shorts.manage', 'comments.manage', 'reports.manage', 'categories.manage', 'copyright.manage',
        ]));
        $support->permissions()->sync($ids([
            'dashboard.view', 'users.manage', 'comments.manage', 'reports.manage', 'copyright.manage',
        ]));

        $super->permissions()->sync(AdminPermission::pluck('id')->all());
    }

    protected function seedSettings(): void
    {
        foreach (SettingService::DEFAULTS as $setting) {
            AppSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    protected function seedCategories(): void
    {
        $categories = [
            ['name' => 'Gaming', 'sort_order' => 1],
            ['name' => 'Music', 'sort_order' => 2],
            ['name' => 'Technology', 'sort_order' => 3],
            ['name' => 'Entertainment', 'sort_order' => 4],
            ['name' => 'Education', 'sort_order' => 5],
            ['name' => 'Sports', 'sort_order' => 6],
            ['name' => 'News', 'sort_order' => 7],
            ['name' => 'Movies', 'sort_order' => 8],
            ['name' => 'Comedy', 'sort_order' => 9],
            ['name' => 'Travel', 'sort_order' => 10],
            ['name' => 'Food', 'sort_order' => 11],
            ['name' => 'Lifestyle', 'sort_order' => 12],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($category['name'])],
                ['name' => $category['name'], 'sort_order' => $category['sort_order'], 'is_active' => true]
            );
        }
    }

    protected function seedHomeSections(): void
    {
        $gaming = Category::where('slug', 'gaming')->first();

        $sections = [
            ['title' => 'Featured', 'type' => 'featured', 'sort_order' => 1, 'item_limit' => 10],
            ['title' => 'Trending Now', 'type' => 'trending', 'sort_order' => 2, 'item_limit' => 12],
            ['title' => 'Recent Uploads', 'type' => 'recent', 'sort_order' => 3, 'item_limit' => 12],
            ['title' => 'Popular', 'type' => 'popular', 'sort_order' => 4, 'item_limit' => 12],
            ['title' => 'Gaming', 'type' => 'category', 'category_id' => $gaming?->id, 'sort_order' => 5, 'item_limit' => 12],
            ['title' => 'Shorts', 'type' => 'shorts', 'sort_order' => 6, 'item_limit' => 10],
        ];

        foreach ($sections as $section) {
            HomeSection::firstOrCreate(['title' => $section['title']], $section + ['is_active' => true]);
        }
    }
}
