<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// MIGRATION (create_users_table)
class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('mobile');
            $table->string('address');
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}

// MODEL (User.php)
class User extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['name', 'email', 'mobile', 'address', 'profile_image'];
}

// CONTROLLER (UserController.php)
class UserController extends \App\Http\Controllers\Controller
{
    // Display all users
    public function index() {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    // Show form for creating a new user
    public function create() {
        return view('users.create');
    }

    // Store a newly created user in the database
    public function store(Request $request) {
        $request->validate([
            'name' => 'required|alpha',
            'email' => 'required|email|unique:users',
            'mobile' => 'required|digits:10',
            'address' => 'required',
            'profile_image' => 'image|mimes:jpg,png,gif|max:2048'
        ]);

        // Handle image upload
        $path = $request->hasFile('profile_image') 
            ? $request->file('profile_image')->store('profile_images', 'public') 
            : null;

        // Create the user
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'profile_image' => $path
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    // Show the form for editing a user
    public function edit(User $user) {
        return view('users.edit', compact('user'));
    }

    // Update the specified user in the database
    public function update(Request $request, User $user) {
        $request->validate([
            'name' => 'required|alpha',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'mobile' => 'required|digits:10',
            'address' => 'required',
            'profile_image' => 'image|mimes:jpg,png,gif|max:2048'
        ]);

        // Handle image upload
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image) {
                Storage::delete('public/' . $user->profile_image);
            }
            $path = $request->file('profile_image')->store('profile_images', 'public');
        } else {
            $path = $user->profile_image;
        }

        // Update user details
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'profile_image' => $path
        ]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    // Delete the specified user from the database
    public function destroy(User $user) {
        if ($user->profile_image) {
            Storage::delete('public/' . $user->profile_image);
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}

// ROUTES (web.php)
Route::resource('users', UserController::class);

// BLADE TEMPLATES

// users/index.blade.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Management</title>
</head>
<body>
    <h1>User Management</h1>
    <a href="{{ route('users.create') }}">Add New User</a>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Address</th>
                <th>Profile Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->mobile }}</td>
                <td>{{ $user->address }}</td>
                <td><img src="{{ asset('storage/' . $user->profile_image) }}" width="50"></td>
                <td>
                    <a href="{{ route('users.edit', $user->id) }}">Edit</a>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

<?php
// users/create.blade.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create User</title>
</head>
<body>
    <h1>Create New User</h1>
    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label>Name</label><br>
        <input type="text" name="name" value="{{ old('name') }}"><br>
        @error('name') <span>{{ $message }}</span><br> @enderror

        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email') }}"><br>
        @error('email') <span>{{ $message }}</span><br> @enderror

        <label>Mobile</label><br>
        <input type="text" name="mobile" value="{{ old('mobile') }}"><br>
        @error('mobile') <span>{{ $message }}</span><br> @enderror

        <label>Address</label><br>
        <input type="text" name="address" value="{{ old('address') }}"><br>
        @error('address') <span>{{ $message }}</span><br> @enderror

        <label>Profile Image</label><br>
        <input type="file" name="profile_image"><br>
        @error('profile_image') <span>{{ $message }}</span><br> @enderror

        <button type="submit">Create</button>
    </form>
</body>
</html>

<?php
// users/edit.blade.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit User</title>
</head>
<body>
    <h1>Edit User</h1>
    <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <label>Name</label><br>
        <input type="text" name="name" value="{{ old('name', $user->name) }}"><br>
        @error('name') <span>{{ $message }}</span><br> @enderror

        <label>Email</label><br>
        <input type="email" name="email" value="{{ old('email', $user->email) }}"><br>
        @error('email') <span>{{ $message }}</span><br> @enderror

        <label>Mobile</label><br>
        <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}"><br>
        @error('mobile') <span>{{ $message }}</span><br> @enderror

        <label>Address</label><br>
        <input type="text" name="address" value="{{ old('address', $user->address) }}"><br>
        @error('address') <span>{{ $message }}</span><br> @enderror

        <label>Profile Image</label><br>
        <input type="file" name="profile_image"><br>
        @if($user->profile_image)
            <img src="{{ asset('storage/'.$user->profile_image) }}" width="100"><br>
        @endif
        @error('profile_image') <span>{{ $message }}</span><br> @enderror

        <button type="submit">Update</button>
    </form>
</body>
</html>
