<?php

namespace App\Http\Controllers;

use App\Models\PreviousProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmationMail;
use App\Http\Traits;
use App\Models\Admin;
use App\Models\Skill;
use App\Models\User;

class AuthController extends Controller
{
    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    public function register(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'first_name' => 'required|string|min:3|max:15',
            'last_name' => 'required|string|min:3|max:15',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:5',
            'confirm_password' => 'required|string|min:5|same:password',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => 0,
                'fcm_token' => $request->fcm_token,
            ]);

            config(['auth.guards.user-api.provider' => 'user']);
            $token = $user->createToken('MyApp', ['user'])->accessToken;

            $response = [
                'user' => $user,
                'token' => $token
            ];

            $message = 'تم التسجيل بنجاح';
            return $this->success($message, $response);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $data = [
                'email' => $request->email,
                'password' => $request->password
            ];
            config(['auth.guards.user-api.provider' => 'user']);
            if (auth('user')->attempt($data)) {

                $user = User::find(Auth::guard('user')->user()->id);
                $token = $user->createToken('MyApp', ['user'])->accessToken;
                $user->fcm_token = $request->fcm_token;
                $user->save();

                $response = [
                    'user' => $user,
                    'token' => $token
                ];
                $message = 'تم تسجيل الدخول';
                return $this->success($message, $response);
            } else
                return $this->failed('كلمة السر خاطئة');
        }
    }

    public function account(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profissionName' => 'string|min:5',
            'speciality' => 'string|min:5',
            'bio' => 'string|min:5',
            'type' => 'required|integer|max:3',
            'birthday' => 'required|date|date_format:Y-m-d',
            'phone_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|max:12',
            'skills' => 'array',
            'skills.*' => 'integer|min:1|exists:categories,id',
            'cover' => 'max:5000|mimes:bmp,jpg,png,jpeg,svg',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $user = User::find(auth()->user()->id);
            $user->birthday = $request->get('birthday');
            $user->phone_number = $request->get('phone_number');
            $user->bio = $request->has('bio') ? $request->get('bio') : '';
            $user->type = $request->get('type');

            if ($request->cover) {
                $cover = $request->file('cover');
                $image = $this->saveImage($cover, 'users');
                $user->cover_image = $image['path'];
            }

            if ($request->get('type') == 0) {
                if ($request->get('profissionName'))
                    $user->profissionName = $request->get('profissionName');

                if ($request->get('speciality'))
                    $user->speciality = $request->get('speciality');

                if ($request->get('skills')) {
                    $skills = $request->get('skills');
                    for ($i = 0; $i < count($skills); $i++)
                        Skill::create([
                            'user_id' => auth()->user()->id,
                            'category_id' => $skills[$i],
                        ]);
                }
            }
            $user->save();
            return $this->success('تم إعداد الحساب', $user);
        }
    }

    public function get_my_profile()
    {
        $user = User::find(auth()->user()->id);
        $skills = DB::table('skills')
            ->join('categories', 'categories.id', '=', 'skills.category_id')
            ->where('user_id', auth()->user()->id)
            ->select('categories.id', 'categories.name', 'skills.rate')
            ->get();
        $response = [
            'user' => $user,
            'skills' => $skills
        ];
        return $this->success('معلومات حسابي', $response);
    }

    public function get_user_profile($id)
    {
        $user = User::find($id);
        $skills = DB::table('skills')
            ->join('categories', 'categories.id', '=', 'skills.category_id')
            ->where('user_id', $id)
            ->select('categories.id', 'categories.name', 'skills.rate')
            ->get();
        $projects = PreviousProject::where('user_id', $id)->get();
        $response = [
            'user' => $user,
            'skills' => $skills,
            'projects' => $projects
        ];
        return $this->success('معلومات حسابي', $response);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'old_password' => 'required|string|min:5',
            'new_password' => 'required|string|min:5',
            'confirm_password' => 'required|string|min:5|same:new_password',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {

            $user = User::find(auth()->user()->id);
            if (!Hash::check($request->old_password, $user->password))
                return $this->failed('Password is wrong');

            $user->password = Hash::make($request->new_password);
            $user->save();
            return $this->success('Change password Success');
        }
    }

    public function logout(Request $request)
    {
        DB::table('oauth_access_tokens')
            ->where('user_id', auth()->user()->id)
            ->where('scopes', '["user"]')
            ->delete();
        return $this->success('تم تسجيل الخروج بنجاح');
    }

    public function cms_login(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'email' => 'required|email|exists:admins,email',
            'password' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $data = [
                'email' => $request->email,
                'password' => $request->password
            ];
            config(['auth.guards.admin-api.provider' => 'admin']);
            if (auth('admin')->attempt($data)) {

                $admin = Admin::find(Auth::guard('admin')->user()->id);
                $token = $admin->createToken('MyApp', ['admin'])->accessToken;

                $response = [
                    'admin' => $data,
                    'token' => $token
                ];
                $message = 'تم تسجيل الدخول إلى نظام إدارة المحتوى (CMS)';
                return $this->success($message, $response);
            } else
                return $this->failed('كلمة السر خاطئة');
        }
    }

    public function changeCMSPassword(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'old_password' => 'required|string|min:5',
            'new_password' => 'required|string|min:5',
            'confirm_password' => 'required|string|min:5|same:new_password',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {

            $user = Admin::find(auth()->user()->id);
            if (!Hash::check($request->old_password, $user->password))
                return $this->failed('Password is wrong');

            $user->password = Hash::make($request->new_password);
            $user->save();
            return $this->success('Change password Success');
        }
    }

    public function logoutCMS()
    {
        DB::table('oauth_access_tokens')
            ->where('user_id', auth()->user()->id)
            ->where('scopes', '["admin"]')
            ->delete();
        return $this->success('Logout Success');
    }

    /*
     *
     * send account confirmation mail
     * Send a 6-digit code to the user's email
     * @return Data by JsonResponse : < code >
     * */
    public function sendConfirmationMail(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);
            $code = ((((((((((rand(1, 9) * 10) + rand(0, 9)) * 10) + rand(0, 9)) * 10) + rand(0, 9)) * 10) + rand(0, 9)) * 10) + rand(0, 9));

            $contact_data = [
                'fullname' => $user['first_name'] . " " . $user['last_name'],
                'email' => $user['email'],
                'subject' => "Verification Message",
                'message' => $code,
            ];

            try {
                Mail::to($user['email'])->send(new ConfirmationMail($contact_data));

                $response = [
                    'code' => $code,
                ];
                $message = 'تم إرسال الكود الى البريد الخاص بك';
                return $this->success($message, $response);
            } catch (\Exception $e) {
                return $this->failed('الرجاء معاودة المحاولة في وقت لاحق');
            }
        } catch (\Exception $e) {
            return self::failed($e->getMessage());
        }
    }

    /*
     *
     * verification
     * Check two code to verify the account
     * @return message by JsonResponse
     * */
    public function verification(Request $request)
    {
        try {
            $rules = [
                'code' => ['required', 'numeric', 'digits:6'],
                'correctCode' => ['required', 'numeric', 'digits:6', 'min:10000'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            if ($request->correctCode != $request->code) {
                return $this->failed('رمز خاطئ');
            }

            $user = User::find(auth()->user()->id);
            $user->is_confirmed = true;
            $user->save();

            $message = 'رمز صحيح، تم تأكيد الحساب';
            return $this->success($message);
        } catch (\Exception $e) {
            return self::failed($e->getMessage());
        }
    }

    /*
     *
     * password Reset
     * Check two code to verify the account
     * Reset user password
     * @return message by JsonResponse
     * */
    public function passwordReset(Request $request)
    {
        try {
            $rules = [
                'code' => ['required', 'numeric', 'digits:6'],
                'correctCode' => ['required', 'numeric', 'digits:6', 'min:10000'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }
            $user = User::find(auth()->user()->id);
            if ($request->correctCode != $request->code) {
                return $this->failed('رمز خاطئ');
            }
            $data['password'] = Hash::make($request->password);
            $user->update($data);
            $message = 'رمز صحيح، تم اعادة تعيين كلمة المرور';
            return $this->success($message);
        } catch (\Exception $e) {
            return self::failed($e->getMessage());
        }
    }

    /*
     *
     * password Reset CMS
     * Check two code to verify the account
     * Reset admin password
     * @return message by JsonResponse
     * */
    public function passwordResetCMS(Request $request)
    {
        try {
            $rules = [
                'code' => ['required', 'numeric', 'digits:6'],
                'correctCode' => ['required', 'numeric', 'digits:6', 'min:10000'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }
            $admin = Admin::find(auth()->user()->id);
            if ($request->correctCode != $request->code) {
                return $this->failed('رمز خاطئ');
            }
            $data['password'] = Hash::make($request->password);
            $admin->update($data);
            $message = 'رمز صحيح، تم اعادة تعيين كلمة المرور';
            return $this->success($message);
        } catch (\Exception $e) {
            return self::failed($e->getMessage());
        }
    }
}
