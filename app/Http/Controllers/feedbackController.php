<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Http\Traits\ResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class feedbackController extends Controller
{
    use ResponseTrait;

    public function getAll()
    {
        return $this->success(
            'التقييمات والشكاوى',
            Feedback::all()
        );
    }

    public function getForGuest()
    {
        return $this->success(
            'التقييمات والشكاوى',
            Feedback::where('status', 1)->get()
        );
    }

    public function feedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feedback' => 'required|string|min:5'
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $user = User::find(auth()->user()->id);
            $username = $user->first_name . ' ' . $user->last_name;
            $feedback = Feedback::create([
                'username' => $username,
                'feedback' => $request->get('feedback')
            ]);
            return $this->success('تم تقديم التقييم', $feedback);
        }
    }

    public function enable($id)
    {
        $feedback = Feedback::find($id);
        if ($feedback === null)
            return $this->failed('There is no feedback with this ID');

        $feedback->status = 1;
        $feedback->save();
        return $this->success('Enabled');
    }

    public function disable($id)
    {
        $feedback = Feedback::find($id);
        if ($feedback === null)
            return $this->failed('There is no feedback with this ID');

        $feedback->status = 0;
        $feedback->save();
        return $this->success('Disabled');
    }
}
