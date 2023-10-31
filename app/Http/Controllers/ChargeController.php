<?php

namespace App\Http\Controllers;

use App\Http\Traits\ResponseTrait;
use App\Models\Wallet;
use App\Models\WalletCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChargeController extends Controller
{
    use ResponseTrait;

    public function createWallet()
    {
        $wallet = Wallet::where('user_id', auth()->user()->id)->first();
        if ($wallet === null) {
            $wallet = Wallet::create([
                'user_id' => auth()->user()->id
            ]);
            return $this->success('تم تجسيل محفظة بنكية لك بنجاح، شكراً', $wallet);
        }
        return $this->failed('لديك محفظة بنكية مسبقاً');
    }

    public function getAmount()
    {
        $wallet = Wallet::where('user_id', auth()->user()->id)->first();
        if ($wallet === null) {
            return $this->failed('ليس لديك محفظة بنكية');
        }
        return $this->success('رصيدك', $wallet->amount);
    }

    public function charge(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return self::failed($validator->errors()->first());
        } else {
            $wallet = Wallet::where('user_id', auth()->user()->id)->first();
            if ($wallet === null) {
                return $this->failed('ليس لديك محفظة بنكية');
            }
            $amount = $wallet->amount;
            $wallet->amount += $request->get('amount');
            $wallet->save();

            WalletCharge::create([
                'user_id' => auth()->user()->id,
                'wallet_id' => $wallet->id,
                'difference' => $request->get('amount'),
                'new_amount' => $wallet->amount,
                'pre_mount' => $amount
            ]);
            return $this->success('تم الشحن بنجاح', $wallet->amount);
        }
    }

    public function getCharges()
    {
        $charges = WalletCharge::join('users', 'users.id', '=', 'wallet_charges.user_id')
            ->select(
                'wallet_charges.difference',
                'wallet_charges.pre_mount',
                'wallet_charges.new_amount',
                'users.first_name',
                'users.last_name'
            )
            ->paginate(10);
        return $this->success('charges log', $charges);
    }
}
