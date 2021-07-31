<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionSuccess;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TravelPackage;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Midtrans\Config;
use Midtrans\Snap;
use PhpParser\Node\Stmt\TryCatch;

class CheckoutController extends Controller
{
    public function index(Request $request, $id)
    {
        $item = Transaction::with(['details', 'travel_package', 'user'])
            ->findOrFail($id);
        return view('pages.checkout', [
            'item' => $item
        ]);
    }

    public function process(Request $request, $id)
    {
        $travel_package = TravelPackage::findOrFail($id);

        $transaction = Transaction::create([
            'travel_packages_id' => $id,
            'users_id' => Auth::user()->id,
            'additional_visa' => 0,
            'transaction_total' => $travel_package->price,
            'transaction_status' => 'IN_CART'
        ]);

        TransactionDetail::create([
            'transactions_id' => $transaction->id,
            'username' => Auth::user()->username,
            'nationality' => 'ID',
            'is_visa' => false,
            'doe_passport' => Carbon::now()->addYears(5)
        ]);

        return redirect()->route('checkout', $transaction->id);
    }

    // Fungsi untuk menghapus data
    public function remove(Request $request, $detail_id)
    {
        // mencari data
        $item = TransactionDetail::findOrFail($detail_id);

        $transaction = Transaction::with(['details', 'travel_package'])
            ->findOrFail($item->transactions_id);

        // Jumlah Total harga
        if ($request->is_visa)
        {
            $transaction->transaction_total -= 190;
            $transaction->additional_visa -= 190;
        }

        $transaction->transaction_total -= $transaction->travel_package->price;

        // save data
        $transaction->save();
        // menghapus item
        $item->delete();

        return redirect()->route('checkout', $item->transactions_id);
    }

    // membuat fungsi create
    public function create(Request $request, $id)
    {
        // untuk validasi data
        $request->validate([
            'username' => 'required|string|exists:users,username',
            'is_visa' => 'required|boolean',
            'doe_passport' => 'required'
        ]);

        $data = $request->all();
        $data['transactions_id'] = $id;

        TransactionDetail::create($data);

        $transaction = Transaction::with(['travel_package'])->find($id);

        // Jumlah Total harga
        if ($request->is_visa)
        {
            $transaction->transaction_total += 190;
            $transaction->additional_visa += 190;
        }

        $transaction->transaction_total += $transaction->travel_package->price;

        // save data
        $transaction->save();

        return redirect()->route('checkout', $id);
    }

    public function success(Request $request, $id)
    {
        $transaction = Transaction::with(['details', 'travel_package.galleries', 'user'])
            ->findOrFail($id);
        $transaction->transaction_status = 'PENDING';

        $transaction->save();

        // set konfigurasi midtrans
    //    Config::$serverKey = config('midtrans.serverKey');
    //    Config::$isProduction = config('midtrans.isProduction');
    //    Config::$isSanitized = config('midtrans.isSanitized');
    //    Config::$is3ds = config('midtrans.is3ds');

    // //    buat array untuk dikirim ke midtrans
    // $midtrans_params = [
    //     'transaction_detail' => [
    //         'order_id' => 'MIDTRANS-' . $transaction->id,
    //         'gross_amount' => (int) $transaction->transaction_total,
    //     ],
    //     'customer_details' => [
    //         'first_name' => $transaction->user->name,
    //         'email' => $transaction->user->email,
    //     ],
    //     'enabled_payments' => ['gopay'],
    //     'vtweb' => []
    // ];


    // // request midtrans
    // try {
    //     // ambil halaman payment di midtrans
    //     $paymentUrl = Snap::createTransaction($midtrans_params)->redirect_url;

    //     // redirect ke halaman midtrans
    //     header('location' . $paymentUrl);
    // } catch (Exception $e) {
    //     echo $e->getMessage();
    // }


        // return $transaction;
        // kirim email e-tiket ke user
        Mail::to($transaction->user)->send(
            new TransactionSuccess($transaction)
        );
        return view('pages.success');
    }
}
