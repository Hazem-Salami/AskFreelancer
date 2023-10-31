<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits;
use App\Models\FinalService;
use App\Models\MediaPost;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\Offer;
use App\Models\User;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class OfferController extends Controller
{
    use Traits\ResponseTrait;
    use Traits\ImageTrait;

    /*
     *
     * create Offer
     * Create a user Offer of the post on the database
     * @return message by JsonResponse
     * */
    public function createOffer(Request $request, $id)
    {
        try {
            Validator::extend('date_multi_format', function ($attribute, $value, $formats) {
                foreach ($formats as $format) {
                    $parsed = date_parse_from_format($format, $value);
                    if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
                        return true;
                    }
                }
                return false;
            });

            $rules = [
                'discription' => ['required', 'string'],
                'price' => ['required', 'numeric'],
                'deliveryDate' => ['required', 'date', 'date_multi_format:"Y-n-j","Y-m-d"', 'after:now'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            $user = User::find(auth()->user()->id);
            $post = Post::find($id);

            $offer = Offer::create([
                'discription' => $request->discription,
                'price' => $request->price,
                'deliveryDate' => $request->deliveryDate,
                'post_id' => $post['id'],
                'user_id' => $user['id']
            ]);

            $message = 'تم إنشاء عرض بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * edit Offer
     * edit a user Offer of the post on the database
     * @return message by JsonResponse
     * */
    public function editOffer(Request $request, $id)
    {
        try {
            $offer = Offer::find($id);

            Validator::extend('date_multi_format', function ($attribute, $value, $formats) {
                foreach ($formats as $format) {
                    $parsed = date_parse_from_format($format, $value);
                    if ($parsed['error_count'] === 0 && $parsed['warning_count'] === 0) {
                        return true;
                    }
                }
                return false;
            });

            $rules = [
                'discription' => ['string'],
                'price' => ['numeric'],
                'deliveryDate' => ['date', 'date_multi_format:"Y-n-j","Y-m-d"', 'after:now'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->failed($validator->errors()->first());
            }

            if ($request->discription)
                $offer->discription = $request->discription;

            if ($request->price)
                $offer->price = $request->price;

            if ($request->deliveryDate)
                $offer->deliveryDate = $request->deliveryDate;

            $offer->save();

            $message = 'تم تعديل العرض بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * delete Offer
     * delete a user Offer of the post on the database
     * @return message by JsonResponse
     * */
    public function deleteOffer($id)
    {
        try {
            $offer = Offer::find($id);
            $offer->delete();
            $message = 'تم حذف العرض بنجاح';
            return $this->success($message);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get Post Offers
     * Get all id post offers
     * @return Data by JsonResponse : array of offers
     * */
    public function getPostOffers($id)
    {
        try {

            $post = Post::find($id);

            $offers = $post->offers;

            foreach ($offers as $offer) {
                $offer->user;
            }

            return $this->success('post ' . $id, $offers);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * accept offer
     * Customer acceptance of the Freelancer offer
     * @return message by JsonResponse
     * */
    public function acceptOffer($id)
    {
        try {

            $offer = Offer::find($id);

            $post = $offer->post;

            $order = $post->order;

            $user = User::find(auth()->user()->id);

            $wallet = Wallet::where('user_id', auth()->user()->id)->first();
            if ($wallet === null) {
                return $this->failed('ليس لديك محفظة بنكية');
            } else {
                if ($wallet->amount - $offer->price < 0)
                    return $this->failed('ليس لديك مايكفي في المحفظة، الرجاء الشحن');
            }

            if ($order != null) {
                return $this->failed('يوحد عرض مقبول مسبقاً');
            }

            if ($post->user_id != $user->id) {
                return $this->failed('ليس لديك الصلاحية بقبول هذا العرض');
            }

            if ($offer->user_id == $user->id) {
                return $this->failed('ليس بالامكان قبول عرضك');
            }

            Order::create([
                'discription' => $offer->discription,
                'price' => $offer->price,
                'deliveryDate' => $offer->deliveryDate,
                'freelancer_id' => $offer->user_id,
                'user_id' => $post->user_id,
                'post_id' => $post->id,
            ]);

            return $this->success('تم قبول العرض بنجاح');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * refuse offer
     * Customer acceptance of the Freelancer offer
     * @return message by JsonResponse
     * */
    public function refuseOffer($id)
    {
        try {

            $offer = Offer::find($id);

            $post = $offer->post;

            $user = User::find(auth()->user()->id);

            if ($post->user_id != $user->id) {
                return $this->failed('ليس لديك الصلاحية برفض هذا العرض');
            }

            if ($offer->user_id == $user->id) {
                return $this->failed('ليس بالامكان رفض عرضك');
            }

            $offer->delete();

            return $this->success('تم رفض العرض بنجاح');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * cancel accept offers
     * Customer cancels acceptance of the Freelancer offer
     * The freelancer refused the customer's approval to my offer
     * @return message by JsonResponse
     * */
    public function cancelOrder($id)
    {
        try {

            $order = Order::find($id);

            $user = User::find(auth()->user()->id);

            if (($order->user_id != $user->id) && ($order->freelancer_id != $user->id)) {
                return $this->failed('ليس لديك الصلاحية للقيام بذلك');
            }

            if ($order->post_id == null) {
                return $this->failed('ليس لديك الصلاحية للقيام بذلك');
            }

            $order->delete();

            $wallet = Wallet::where('user_id', $order->user_id)->first();
            if ($wallet === null) {
                return $this->failed('ليس لديك محفظة بنكية');
            } else {
                $amount = $wallet->amount;
                $wallet->amount  = $amount + $order->price;
                $wallet->save();
            }

            return $this->success('تم إلغاء قبول العرض');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * get orders
     * @return Data by JsonResponse : array of offers
     * */
    public function getOrders()
    {
        try {

            $user = User::find(auth()->user()->id);

            $userorders = $user->userorders;
            $userorders = $userorders->whereNotNull('post_id');

            foreach ($userorders as $userorder) {
                $userorder->freelancer;
                $userorder->post;
            }

            $freelancerorders = $user->freelancerorders;
            $freelancerorders = $freelancerorders->whereNotNull('post_id');

            foreach ($freelancerorders as $freelancerorder) {
                $freelancerorder->user;
                $freelancerorder->post;
            }

            return $this->success('orders ', [
                'freelancer orders' => $freelancerorders, 'user orders' => $userorders
            ]);
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * accept accept offers
     * The freelancer accepted the customer's approval to my offer
     * @return message by JsonResponse
     * */
    public function acceptAcceptOffer($id)
    {
        try {

            $order = Order::find($id);

            $user = User::find(auth()->user()->id);

            if ($order->freelancer_id != $user->id) {
                return $this->failed('ليس لديك الصلاحية بالموافقة على قبول العرض');
            }

            $walletFreelancer = Wallet::where('user_id', auth()->user()->id)->first();
            if ($walletFreelancer === null) {
                return $this->failed('ليس لديك محفظة بنكية');
            }

            $wallet = Wallet::where('user_id', $order->user_id)->first();
            if ($wallet === null) {
                return $this->failed('الزبون ليس لديه محفظة بنكية');
            } else {
                $amount = $wallet->amount;
                $wallet->amount  = $amount - $order->price;
                $wallet->save();
            }

            $post = $order->post;

            $mediaposts = $post->mediaposts;

            foreach ($mediaposts as $mediapost) {
                if (File::exists(public_path($mediapost->path)))
                    File::delete(public_path($mediapost->path));
                $mediapost->delete();
            }

            $offers = $post->offers;

            foreach ($offers as $offer) {
                $offer->delete();
            }

            $postcategories = $post->postcategories;

            foreach ($postcategories as $postcategory) {
                $postcategory->delete();
            }

            $post->delete();

            $order->post_id = null;
            $order->save();

            return $this->success('تمت الموافقة على قبول العرض');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * delete orders
     * @return message by JsonResponse
     * */
    public function deleteOrders()
    {
        try {

            $orders = Order::where('deliveryDate', '<', Carbon::now()->format('Y-m-d'))->get();

            foreach ($orders as $order) {
                if ($order->post_id == null) {
                    $wallet = Wallet::where('user_id', $order->user_id)->first();
                    if ($wallet != null) {
                        $amount = $wallet->amount;
                        $wallet->amount  = $amount + $order->price;
                        $wallet->save();
                    }
                }
                $order->delete();
            }

            return $this->success('تم حذف جميع الطلبات الغير منجزة قبل الوقت المحدد');
        } catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    /*
     *
     * A freelancer sends files of final service
     * @return Json message
     * */
    public function sendFinalService(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'media' => 'array',
            'media.*' => 'required',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $mytime = Carbon::now()->format('Y-m-d');
            $order = Order::find($id);
            if ($order === null)
                return $this->failed('الطلب غير موجود');

            if ($mytime > $order->deliveryDate)
                return $this->failed('لقد اجتزت المهلة المتفق عليها للأسف');

            if ($request->has('media')) {
                $media = $request->file('media');
                if ($media != null) {
                    $i = 0;
                    foreach ($media as $file) {

                        $i++;
                        $media = $this->saveImage($file, 'final service', $i);

                        FinalService::create([
                            'file' => $media['path'],
                            'order_id' => $id,
                        ]);
                    }
                }
            }
            return $this->success('تم إرسال ملفات المشروع');
        }
    }

    /*
     *
     * A customer get files of final service
     * @return Json message
     * */
    public function getFinalService($id)
    {
        $serviceFiles = FinalService::where('order_id', $id)->get();
        $response = [
            'order_id' => $id,
            'files' => $serviceFiles
        ];
        return $this->success('ملفات المشروع', $response);
    }

    /*
     *
     * A customer suucess the order
     * @return Json message
     * */
    public function succeessOrder($id)
    {
        $order = Order::find($id);
        if ($order === null)
            return $this->failed('لا يوجد طلب');

        $wallet = Wallet::where('user_id', $order->freelancer_id)->first();
        if ($wallet === null) {
            return $this->failed('لا يوجد محفظة بنكية للمستقبل');
        } else {
            $amount = $wallet->amount;

            Sale::create([
                'amount' => $order->price * 0.1
            ]);
            $wallet->amount  = $amount + ($order->price * 0.9);
            $wallet->save();
            $order->delete();
        }
        return $this->success('تم إنهاء العملية بنجاح');
    }

    public function getSales()
    {
        $now = Carbon::now();
        $salesYear = Sale::whereYear('created_at', '=', $now->year)->sum('amount');
        $salesMonth = Sale::whereMonth('created_at', '=', $now->month)->sum('amount');
        $salesDay = Sale::whereDay('created_at', '=', $now->day)->sum('amount');
        $response = [
            'day' => $salesDay,
            'month' => $salesMonth,
            'year' => $salesYear
        ];
        return $this->success('الأرباح', $response);
    }
}
