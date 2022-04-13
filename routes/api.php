<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthMasterMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get("/", function(Request $requset){
    return "YAKOPI API LARAVEL v1.0";
});

Route::post("/auth", function (Request $request){

    $username = $request->email;
    $password = $request->password;
    
    $account = DB::select("SELECT * FROM yakopi_pengguna WHERE yakopi_pengguna.username=? OR yakopi_pengguna.email=?",[$username,$username]);

    if(count($account)>0){
        $u = $account[0]->username;
        $p = $account[0]->password;
        $e = $account[0]->email;
        
        $sha1p = sha1($password);

        if($u==$username && $sha1p==$p || $e==$username && $sha1p==$p){

            $update = DB::update("UPDATE yakopi_pengguna SET last_login=NOW() WHERE username=?",[$username]);
            $modul = DB::select("SELECT * FROM yakopi_hak_akses WHERE nama_hak_akses=?",[$account[0]->hak_akses]);

            return [
                "success"=>true,
                "data"=>$account[0],
                "modul"=>$modul[0],
                "token"=>Crypt::encryptString(json_encode($account[0]))
            ];
        }
        else{
            return [
                "success"=>false,
                "msg"=>"Login gagal"
            ];
        }
        
    
    }
    else{
        return [
            "success"=>false,
            "msg"=>"Tidak ditemukan akun tersebut"
        ];
    }

});


Route::middleware([AuthMasterMiddleware::class])->group(function () {
    Route::post("/cek-presensi", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $presensi_today = DB::select("SELECT * FROM yakopi_absen WHERE tgl_absen=CURDATE() AND id_pengguna=?",[$json->id_pengguna]);
        
        if(count($presensi_today)==0){

            return [
                "success"=>true,
                "data"=>[
                    "jam_masuk_absen"=>null,
                    "jam_keluar_absen"=>null
                ]
                ];
            
        }
        else{
            return [
                "success"=>true,
                "data"=>[
                    "jam_masuk_absen"=>$presensi_today[0]->jam_masuk_absen,
                    "jam_keluar_absen"=>$presensi_today[0]->jam_keluar_absen
                ]
                ];
        }
    });

    Route::post("/presensi-masuk", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $filename = $request->filename;
        $timezone = $request->timezone;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
    
        $checkexist = DB::select("SELECT * FROM yakopi_absen WHERE tgl_absen=CURDATE() AND id_pengguna=?",[$json->id_pengguna]);
        
        if(count($checkexist)==0){
            $insertpresensimasuk = DB::insert("INSERT INTO yakopi_absen VALUES (NULL,?,NOW(),?,NULL,?,NULL,?,?,NULL,NULL)",[$json->id_pengguna,$timezone,"assets/absenMasuk/".$filename,$latitude,$longitude]);
    
            return [
                "success"=>true,
                "msg"=>"Berhasil melakukan presensi."
            ];
        }
        else{
            return [
                "success"=>false,
                "msg"=>"Sudah melakukan presensi."
            ];
        }
    });
});