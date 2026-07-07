<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
  
class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'id' => 1,
            'first_name' => 'Balamurugan', 
            'last_name' => 'M',
            'email' => 'admin@warpe.in',
            'password' => bcrypt('Parthiv$$99'),
            'confirm_password' => bcrypt('Parthiv$$99'),
            'image' => '',
            'message_checked_at' => '2023-09-05 11:15:44',
            'notification_checked_at' => '2023-09-05 11:15:44',
            'job_title' => 'Admin',
            'note' => 'NULL',
            'address' => 'NULL',
            'alternative_address' => 'NULL',
            'phone' => 'NULL',
            'alternative_phone' => 'NULL',
            'dob' => '1994-03-31',
            'ssn' => 'NULL',
            'gender' => '0',
            'sticky_note' => 'NULL',
            'skype' => 'NULL',
            'language' => 'NULL',

        ]);
        
        $role = Role::create(['name' => 'Admin']);
         
        $permissions = Permission::pluck('id','id')->all();
       
        $role->syncPermissions($permissions);
         
        $user->assignRole([$role->id]);
    }
}