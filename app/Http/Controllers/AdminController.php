<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;

use App\Http\Traits\ResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->success('Admins', Admin::paginate(10));
    }


    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:2|max:15',
            'email' => 'required|unique:admins,email',
            'password' => 'required|string|min:5',
            'role_id' => 'required|integer|min:1|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {
            $admin = Admin::create([
                'username' => $request->get('username'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->password),
                'role_id' => $request->get('role_id'),
            ]);

            $message = 'Creating Success';
            return $this->success($message, $admin);
        }
    }

    public function show($id)
    {
        $admin = Admin::find($id);
        if ($admin === null)
            return $this->failed("There is no admin with this ID");

        return $this->success('Admin ' . $id, $admin);
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::find($id);
        if ($admin === null)
            return $this->failed("There is no admin with this ID");

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:2|max:15',
            'role_id' => 'required|integer|min:1|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        } else {

            $admin->username = $request->get('username');
            $admin->role_id = $request->get('role_id');
            $admin->save();

            $message = 'Updating Success';
            return $this->success($message, $admin);
        }
    }

    public function destroy($id)
    {
        $admin = Admin::find($id);
        if ($admin === null)
            return $this->failed("There is no admin with this id");

        Admin::destroy($id);
        return $this->failed('Deleted Success!');
    }
}
