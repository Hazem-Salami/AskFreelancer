<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\MediaIdentity;
use Illuminate\Support\Facades\File;

class IdentityDocumentionController extends Controller
{

    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    /*
     *
     * send identity document
     * Storing the user's identity document on the database for acceptance or rejection by the admin
     * @return message by JsonResponse
     * */
    public function sendIdentityDocument(Request $request)
    {
        try {

            $user = User::find(auth()->user()->id);

            if ($user->is_documented == true) {
                $message = 'إن الحساب موثق مسبقاً';
                return $this->success($message);
            }

            $rules = [
                'media' => ['required', 'array'],
                'media.*' => 'required|max:20000|mimes:bmp,jpg,png,jpeg',
                'delete_media' => 'array',
                'delete_media.*' => 'required|integer|min:1|exists:media_identities,id',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $media = $request->file('media');
            if ($media != null) {
                $i = 0;
                foreach ($media as $file) {

                    $i++;
                    $media = $this->saveImage($file, 'freelancers identity documents', $i);

                    $medias[] = MediaIdentity::create([
                        'path' => $media['path'],
                        'user_id' => $user->id,
                    ]);
                }
            }

            if ($request->has('delete_media')) {
                $delete_media = $request->get('delete_media');

                foreach ($delete_media as $media) {
                    $media_record = MediaIdentity::find($media);

                    if (File::exists(public_path($media_record->path)))
                        File::delete(public_path($media_record->path));
                    $media_record->delete();
                }
            }

            $message = 'تم ارسال الوثائق بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * respone identity documentation
     * Acceptance or rejection of user documents by the admin
     * true for Acceptance | false for rejection
     * @return message by JsonResponse
     * */
    public function ResponeIdentityDocumentation(Request $request)
    {
        try {

            $rules = [
                'user_id' => ['required', 'numeric', 'exists:users,id'],
                'is_documented' => ['required', 'boolean'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $user = User::find($request->user_id);
            $media = $user->mediaidentitys;

            if (count($media) != 0) {
                if ($request->is_documented == true) {
                    $user->is_documented = $request->is_documented;
                    $user->save();
                } else {
                    foreach ($media as $oneMedia) {
                        if (File::exists(public_path($oneMedia->path)))
                            File::delete(public_path($oneMedia->path));
                        $oneMedia->delete();
                    }
                    $user->is_documented = $request->is_documented;
                    $user->save();
                }
                $message = 'تم الاستجابة للوثائق بنجاح';
                return $this->success($message);
            }

            $message = 'حصل خطأ، لا يوجد وثائق خاصة بالمستخدم';
            return $this->failed($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get identity documentation
     * get user identification documents
     * @return message by JsonResponse
     * */
    public function GetIdentityDocumentation()
    {
        try {

            $users = User::join('media_identities', 'media_identities.user_id', '=', 'users.id')
                ->where('users.is_documented', false)
                ->select('users.*')
                ->distinct()
                ->get();
                
                foreach ($users as $user) {
                    $user->mediaidentitys;
                }

            return $this->success('users ', $users);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }
}
