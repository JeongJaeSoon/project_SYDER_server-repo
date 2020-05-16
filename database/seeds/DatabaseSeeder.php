<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
//        $this->call(UsersTableSeeder::class);
        // 라라벨에 기본 내장된 User 모델 팩토리를 이용
        $user = factory(App\User::class)->create([
            'account' => 'fcm_account',
            'name' => 'fcm_name'
        ]);
        // User-Device 간 관계를 이용해서 새 더미 레코드를 생성
        $user->devices()->create([
            'device_id' => str_random(16),
            'push_service_id' => str_random(152),
        ]);
    }
}
