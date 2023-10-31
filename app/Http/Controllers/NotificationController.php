<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\ResponseTrait;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

class NotificationController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $notification = UserNotification::join('notifications', 'notifications.id', '=', 'user_notifications.notification_id')
            ->where('user_notifications.user_id', Auth::user()->id)
            ->select(
                'notifications.id',
                'notifications.title',
                'notifications.body',
                'notifications.created_at'
            )
            ->orderBy('notifications.created_at', 'desc')
            ->paginate(10);

        return $this->success("إشعاراتي", $notification);
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:5',
            'body' => 'required|string|min:5'
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $notification = Notification::create([
                'title' => $request->get('title'),
                'body' => $request->get('body')
            ]);

            $users = User::pluck('id');

            foreach ($users as $id) {
                UserNotification::create([
                    'user_id' => $id,
                    'notification_id' => $notification->id
                ]);
                $this->push(
                    $notification->title,
                    $notification->body,
                    "global notification",
                    $id
                );
            }
            return $this->success('تم إرسال الإشعار', $notification);
        }
    }

    public function push($title, $body, $data1, $id = null)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 20);

        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($body);
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['data' => $data1]);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $user = User::find($id);
        $token = $user->fcm_token;
        // return $token;
        //        $token = "drv3kHauT7Cvm7G5CgH6N7:APA91bGrptNDYSGUXUfZN6yZ66UqrrCGtBsCW1MHA0I9A8KOaAutJARpgaKDh36nFjt-FJyCiHCInYvqYHbSW15b1TK_0NEJJvtq0YxQhu9zEOvluvKy4wVpcEabQxLMB1gmdTKZDzll";
        if ($token) {
            $response = FCM::sendTo($token, $option, $notification, $data);
            //   return $response->numberSuccess();
        }

        //        $optionBuilder = new OptionsBuilder();
        //        $optionBuilder->setTimeToLive(60*20);
        //
        //        $notificationBuilder = new PayloadNotificationBuilder('my title');
        //        $notificationBuilder->setBody('Hello world')
        //            ->setSound('default');
        //
        //        $dataBuilder = new PayloadDataBuilder();
        //        $dataBuilder->addData(['a_data' => 'my_data']);
        //
        //        $option = $optionBuilder->build();
        //        $notification = $notificationBuilder->build();
        //        $data = $dataBuilder->build();
        //
        //        $token = "drv3kHauT7Cvm7G5CgH6N7:APA91bGrptNDYSGUXUfZN6yZ66UqrrCGtBsCW1MHA0I9A8KOaAutJARpgaKDh36nFjt-FJyCiHCInYvqYHbSW15b1TK_0NEJJvtq0YxQhu9zEOvluvKy4wVpcEabQxLMB1gmdTKZDzll";
        //
        //        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        //          return $downstreamResponse->tokensWithError();
        //        $downstreamResponse->numberSuccess();
        //        $downstreamResponse->numberFailure();
        //        $downstreamResponse->numberModification();
        //
        //// return Array - you must remove all this tokens in your database
        //        $downstreamResponse->tokensToDelete();
        //
        //// return Array (key : oldToken, value : new token - you must change the token in your database)
        //        $downstreamResponse->tokensToModify();
        //
        //// return Array - you should try to resend the message to the tokens in the array
        //        $downstreamResponse->tokensToRetry();
        //
        //// return Array (key:token, value:error) - in production you should remove from your database the tokens
        //        $downstreamResponse->tokensWithError();

    }
}
