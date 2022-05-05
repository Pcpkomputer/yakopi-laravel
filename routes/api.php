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

    //GENERAL API START

    // INFORMATION API START

    Route::get("/mobilebuildnumber", function(Request $request){
        $version = DB::select("SELECT buildnumber,changelog_mobile FROM yakopi_identitas WHERE id_profile=1");
        return [
            "buildnumber"=>$version[0]->buildnumber,
            "changelog_mobile"=>$version[0]->changelog_mobile
        ];
    });

    // LOCATION API START

    Route::get("/city", function (Request $request){
        $city = DB::select("SELECT * FROM yakopi_cities");
        return $city;
    });

    Route::get("/city/{id}", function (Request $request, $id){
        $city = DB::select("SELECT * FROM yakopi_cities WHERE prov_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$city
        ];
    });

    Route::get("/district", function (Request $request){
        $district = DB::select("SELECT * FROM yakopi_districts");
        return [
            "success"=>true,
            "data"=>$district
        ];
    });

    Route::get("/district/{id}", function (Request $request, $id){
        $district = DB::select("SELECT * FROM yakopi_districts WHERE city_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$district
        ];
    });

    Route::get("/province", function (Request $request){
        $province = DB::select("SELECT * FROM yakopi_provinces");
        return [
            "success"=>true,
            "data"=>$province
        ];
    });

    Route::get("/province/{id}", function (Request $request, $id){
        $province = DB::select("SELECT * FROM yakopi_provinces WHERE prov_id=?",[$id]);
        return [
            "success"=>true,
            "data"=>$province
        ];
    });

    // LOCATION API END

    // PHOTO API START

    Route::get("/photo_restoration", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_restoration LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::get("/photo_comdev", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_comdev LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::get("/photo_research", function (Request $request){
        $photo = DB::select("SELECT * FROM yakopi_photo_research LIMIT 10");
        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    // PHOTO API END

    // PROJECT API START

    Route::get("/project", function (Request $request){
        $project = DB::select("SELECT * FROM yakopi_project");
        return [
            "success"=>true,
            "data"=>$project
        ];
    });

    Route::get("/project/{id}", function (Request $request, $id){
        $project = DB::select("SELECT * FROM yakopi_project WHERE id_project=?",[$id]);
        return [
            "success"=>true,
            "data"=>$project
        ];
    });

    // PROJECT API END

    // GENERAL API END


    // PRESENSI START

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

    Route::post("/presensi-pulang", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $filename = $request->filename;
        $timezone = $request->timezone;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $updatepresensi = DB::insert("UPDATE yakopi_absen SET jam_keluar_absen=?,foto_absen_keluar=?, lat_absen_keluar=?, long_absen_keluar=? WHERE id_pengguna=? AND tgl_absen=CURDATE()",[$timezone,$filename,$latitude,$longitude,$json->id_pengguna]);
    
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan presensi pulang"
        ];
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
                "msg"=>"Berhasil melakukan presensi"
            ];
        }
        else{
            return [
                "success"=>false,
                "msg"=>"Sudah melakukan presensi"
            ];
        }
    });

    // PRESENSI END

    // PROFILE START

    Route::get("/profile", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $profile = DB::select("SELECT * FROM yakopi_pengguna WHERE id_pengguna=?",[$json->id_pengguna]);
        return [
            "success"=>true,
            "data"=>$profile[0]
        ];
    });

    Route::post("/update-profile", function (Request $request){
        $token = $request->bearerToken();

        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $username = $request->username;
        $namalengkap = $request->namalengkap;
        $jenkel = $request->jenkel;
        $tanggallahir = $request->tanggallahir;
        $alamat = $request->alamat;
        $email = $request->email;
        $notelepon = $request->notelepon;
        $newpassword = $request->newpassword;
        $fotoprofil = $request->fotoprofil;

        if($newpassword==""){
            $newpassword = $json->password;
        }
        else{
            $newpassword = sha1($request->newpassword);
        }
    
    
        $update = DB::update("UPDATE yakopi_pengguna SET password=?,email=?,no_hp=?,nama_lengkap=?,foto_pengguna=?,jenkel=?,tgl_lahir=?,alamat=? 
        WHERE id_pengguna=?",[$newpassword,$email,$notelepon,$namalengkap,$fotoprofil,$jenkel,$tanggallahir,$alamat,$json->id_pengguna]);

        $newdata = DB::select("SELECT * FROM yakopi_pengguna WHERE id_pengguna=?",[$json->id_pengguna]);

        $modul = DB::select("SELECT * FROM yakopi_hak_akses WHERE nama_hak_akses=?",[$newdata[0]->hak_akses]);

        return [
            "success"=>true,
            "msg"=>"Berhasil mengubah profil",
            "credentials"=>[
                "data"=>$newdata[0],
                "modul"=>$modul[0],
                "token"=>Crypt::encryptString(json_encode($newdata[0]))
            ]
        ];

    });


    // PROFILE END

    // GENERAL API END

    // RESTORATION START

    // LAND ASSESSMENT START
    Route::get("/land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
        return [
            "success"=>true,
            "data"=>$land_assessment
        ];
    });

    Route::get("/land-assessment/{id}", function (Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id]);
        return [
            "success"=>true,
            "data"=>$land_assessment
        ];
    });

    Route::get("/history-land-assessment/{id}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("SELECT * FROM yakopi_land_assessment WHERE created_by=?",[$json->id_pengguna]);
        return [
            "success"=>true,
            "data"=>$land_assessment
        ];
    });

    Route::post("/land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $site_code = $request->site_code;
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $date_land_assessment = $request->date_land_assessment;
        $lat_land_assessment = $request->coordinate["latitude"];
        $long_land_assessment = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $posisi_site = $request->site_position;
        $perkiraan_jumlah_plot = $request->estimated_number_of_plots_per_site;
        $sejarah_lokasi = $request->history_location;
        $akses_jalan = $request->road_access;
        $kondisi_lahan = $request->land_condition;
        $tegakan_mangrove = $request->the_presence_of_mangrove_stands;
        $adanya_perdu = $request->shrubs;
        $potensi_gangguan_hewan_peliharaan = $request->pet_nuisance;
        $potensi_hama = $request->pest_potential;
        $potensi_gangguan_tritip = $request->tritype_disorder_potential;
        $potensi_gangguan_kepiting = $request->crab_interference_potential;
        $potensi_gempuran_ombak = $request->potential_for_waves;
        $jenis_tanah = $request->type_of_soil;
        $catatan_khusus_1 = $request->important_information_from_group_members;
        $catatan_khusus_2 = $request->other_important_information_from_group_members;
        $nama_surveyor = $request->surveyor;
        $ttd_surveyor = "";
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $land_assessment = DB::insert("INSERT INTO yakopi_land_assessment (id_land_assessment,site_code,id_project,id_provinces,id_cities,id_districts,date_land_assessment,lat_land_assessment,long_land_assessment,nama_desa,nama_dusun,posisi_site,perkiraan_jumlah_plot,sejarah_lokasi,akses_jalan,kondisi_lahan,tegakan_mangrove,adanya_perdu,potensi_gangguan_hewan_peliharaan,potensi_hama,potensi_gangguan_tritip,potensi_gangguan_kepiting,potensi_gempuran_ombak,jenis_tanah,catatan_khusus_1,catatan_khusus_2,nama_surveyor,ttd_surveyor,created_by,created_time,status) VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$site_code,$id_project,$id_provinces,$id_cities,$id_districts,$date_land_assessment,$lat_land_assessment,$long_land_assessment,$nama_desa,$nama_dusun,$posisi_site,$perkiraan_jumlah_plot,$sejarah_lokasi,$akses_jalan,$kondisi_lahan,$tegakan_mangrove,$adanya_perdu,$potensi_gangguan_hewan_peliharaan,$potensi_hama,$potensi_gangguan_tritip,$potensi_gangguan_kepiting,$potensi_gempuran_ombak,$jenis_tanah,$catatan_khusus_1,$catatan_khusus_2,$nama_surveyor,$ttd_surveyor,$created_by,$created_time,$status]);
        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan land assessment"
        ];
    });

    Route::post("/approve-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $status = 1;

        $land_assessment = DB::update("UPDATE yakopi_land_assessment SET status=? WHERE id_land_assessment=?",[$status,$id_land_assessment]);

        return [
            "success"=>true,
            "msg"=>"Berhasil mengkonfirmasi land assessment"
        ];
    });

    Route::post("/reject-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $status = 2;

        $land_assessment = DB::update("UPDATE yakopi_land_assessment SET status=? WHERE id_land_assessment=?",[$status,$id_land_assessment]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menolak land assessment"
        ];
    });

    Route::get("/photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $photo_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_photo WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$photo_land_assessment
        ];
    });

    Route::get("/video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $video_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_video WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$video_land_assessment
        ];
    });

    Route::get("/drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;

        $drone_land_assessment = DB::select("SELECT * FROM yakopi_land_assessment_drone WHERE id_land_assessment=?",[$id_land_assessment]);
        return [
            "success"=>true,
            "data"=>$drone_land_assessment
        ];
    });

    // LAND ASSESSMENT FINISH

    // COMMUNITY DEVELOPMENT START

    Route::get("/community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("SELECT * FROM yakopi_community_register");
    
        return [
            "success"=>true,
            "data"=>$community_development
        ];
    });

    Route::get("/community-register/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);
    
        return [
            "success"=>true,
            "data"=>$community_development
        ];
    });
    
    Route::post("/community-register", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $nomor_mou = $request->nomor_mou;
        $nama_kelompok = $request->nama_kelompok;
        $ketua_kelompok = $request->ketua_kelompok;
        $id_project = $request->id_project;
        $id_provinces = $request->id_provinces;
        $id_cities = $request->id_cities;
        $id_districts = $request->id_districts;
        $nama_desa = $request->nama_desa;
        $nama_dusun = $request->nama_dusun;
        $jumlah_site = $request->jumlah_site;
        $jumlah_plot = $request->jumlah_plot;
        $luas_area_mou = $request->luas_area_mou;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
    
        $insertcommunity = DB::insert("INSERT INTO yakopi_community_register VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",[$nomor_mou,$nama_kelompok,$ketua_kelompok,$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jumlah_site,$jumlah_plot,$luas_area_mou,$created_by,$created_time]);
    
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });
    
    Route::get("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("SELECT * FROM yakopi_silvoshery");
    
        return [
            "success"=>true,
            "data"=>$silvoshery
        ];
    });

    Route::get("/silvoshery/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);
    
        return [
            "success"=>true,
            "data"=>$silvoshery
        ];
    });
    
    Route::post("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $id_project = $request->id_project;
        $id_provinces = $request->id_provinces;
        $id_cities = $request->id_cities;
        $id_districts = $request->id_districts;
        $nama_desa = $request->nama_desa;
        $nama_dusun = $request->nama_dusun;
        $kode_silvoshery = $request->kode_silvoshery;
        $pemilik_tambak = $request->pemilik_tambak;
        $jumlah_tanaman = $request->jumlah_tanaman;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
    
        $insertsilvoshery = DB::insert("INSERT INTO yakopi_silvoshery VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?)",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$kode_silvoshery,$pemilik_tambak,$jumlah_tanaman,$created_by,$created_time]);
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });
});