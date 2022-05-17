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

        $updatepresensi = DB::insert("UPDATE yakopi_absen SET jam_keluar_absen=?,foto_absen_keluar=?, lat_absen_keluar=?, long_absen_keluar=? WHERE id_pengguna=? AND tgl_absen=CURDATE()",[$timezone,"assets/img/absenKeluar/".$filename,$latitude,$longitude,$json->id_pengguna]);
    
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
            $insertpresensimasuk = DB::insert("INSERT INTO yakopi_absen VALUES (NULL,?,NOW(),?,NULL,?,NULL,?,?,NULL,NULL)",[$json->id_pengguna,$timezone,"assets/img/absenMasuk/".$filename,$latitude,$longitude]);
    
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

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_land_assessment=?
        ",[$id]);
        return [
            "success"=>true,
            "data"=>$land_assessment[0]
        ];
    });

    Route::get("/history-land-assessment/{id}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $land_assessment = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_land_assessment AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);
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

    Route::post("/photo-land-assessment", function (Request $request){
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

    Route::post("/add-photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_photo = $request->keterangan_land_assessment_photo;
        $link_land_assessment_photo = $request->link_land_assessment_photo;
        $file_land_assessment_photo = $request->file_land_assessment_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_photo (id_land_assessment_photo,id_land_assessment,keterangan_land_assessment_photo,link_land_assessment_photo,file_land_assessment_photo,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_photo,$link_land_assessment_photo,$file_land_assessment_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan photo land assessment"
        ];
    });

    Route::delete("/delete-photo-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_photo = $request->id_land_assessment_photo;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_photo WHERE id_land_assessment_photo=?",[$id_land_assessment_photo]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $photo_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_photo WHERE id_land_assessment_photo=?",[$id_land_assessment_photo]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus photo land assessment"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus photo land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus photo land assessment. Data tidak ditemukan"
            ];
        }
    });

    Route::post("/video-land-assessment", function (Request $request){
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

    Route::post("/add-video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_video = $request->keterangan_land_assessment_video;
        $link_land_assessment_video = $request->link_land_assessment_video;
        $file_land_assessment_video = $request->file_land_assessment_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_video (id_land_assessment_video,id_land_assessment,keterangan_land_assessment_video,link_land_assessment_video,file_land_assessment_video,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_video,$link_land_assessment_video,$file_land_assessment_video,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan video land assessment"
        ];
    });

    Route::delete("/delete-video-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_video = $request->id_land_assessment_video;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_video WHERE id_land_assessment_video=?",[$id_land_assessment_video]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $video_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_video WHERE id_land_assessment_video=?",[$id_land_assessment_video]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus video land assessment"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus video land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus video land assessment. Data tidak ditemukan"
            ];
        }

    });

    Route::post("/drone-land-assessment", function (Request $request){
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

    Route::post("/add-drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $keterangan_land_assessment_drone = $request->keterangan_land_assessment_drone;
        $link_land_assessment_drone = $request->link_land_assessment_drone;
        $file_land_assessment_drone = $request->file_land_assessment_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone_land_assessment = DB::insert("INSERT INTO yakopi_land_assessment_drone (id_land_assessment_drone,id_land_assessment,keterangan_land_assessment_drone,link_land_assessment_drone,file_land_assessment_drone,created_by,created_time) VALUES (null,?,?,?,?,?,?)",[$id_land_assessment,$keterangan_land_assessment_drone,$link_land_assessment_drone,$file_land_assessment_drone,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Berhasil menambahkan drone land assessment"
        ];

    });

    Route::delete("/delete-drone-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment_drone = $request->id_land_assessment_drone;

        $cekIdLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment_drone WHERE id_land_assessment_drone=?",[$id_land_assessment_drone]);

        if(count($cekIdLandAssessment)>0){
            $id_land_assessment = $cekIdLandAssessment[0]->id_land_assessment;

            $cekStatus = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

            if($cekStatus[0]->status=="0"){
                $drone_land_assessment = DB::delete("DELETE FROM yakopi_land_assessment_drone WHERE id_land_assessment_drone=?",[$id_land_assessment_drone]);
            
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus drone land assessment"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Gagal menghapus drone land assessment. Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Gagal menghapus drone land assessment. Data tidak ditemukan"
            ];
        }

    });

    Route::delete("/delete-land-assessment", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_land_assessment = $request->id_land_assessment;
        $cekLandAssessment = DB::select("SELECT * FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);

        if(count($cekLandAssessment) > 0){
            if($cekLandAssessment[0]->status == 0){
                $land_assessment = DB::delete("DELETE FROM yakopi_land_assessment WHERE id_land_assessment=?",[$id_land_assessment]);
                return [
                    "success"=>true,
                    "msg"=>"Berhasil menghapus land assessment"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Land assessment sudah di konfirmasi"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Land assessment tidak ditemukan"
            ];
        }
    });

    // LAND ASSESSMENT FINISH

    // SEED COLLECTING START

    Route::get("/seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$seed_collecting
        ];
    });

    Route::get("/seed-collecting/{id}", function (Request $request,$id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_collecting_seed=?
        ",[$id]);

        $detail_collecting_seed = DB::select("
        SELECT * FROM yakopi_detail_collecting_seed WHERE id_collecting_seed=?",[$id]);

        return [
            "success"=>true,
            "dataSeedCollecting"=>$seed_collecting[0],
            "dataDetailCollectingSeed"=>$detail_collecting_seed[0]
        ];
    });

    Route::get("/history-seed-collecting/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $history_seed_collecting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_collecting_seed AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$history_seed_collecting
        ];
    });

    Route::post("/seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $lat_collecting_seed = $request->coordinate["latitude"];
        $long_collecting_seed = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $trasportasi_1 = $request->transportation_used_1;
        $trasportasi_2 = $request->transportation_used_2;
        $catatan_1 = $request->important_information_from_group_members;
        $catatan_2 = $request->other_important_information_from_group_members;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = '';
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $seedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed (id_collecting_seed,id_project,id_provinces,id_cities,id_districts,lat_collecting_seed,long_collecting_seed,nama_desa,nama_dusun,trasportasi_1,trasportasi_2,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_project,$id_provinces,$id_cities,$id_districts,$lat_collecting_seed,$long_collecting_seed,$nama_desa,$nama_dusun,$trasportasi_1,$trasportasi_2,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time]);
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::post("/approve-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $status = 1;

        $approveSeedCollecting = DB::update("UPDATE yakopi_collecting_seed SET status=? WHERE id_collecting_seed=?",[$status,$id_collecting_seed]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $status = 2;

        $rejectSeedCollecting = DB::update("UPDATE yakopi_collecting_seed SET status=? WHERE id_collecting_seed=?",[$status,$id_collecting_seed]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $kind_seed_collecting = DB::select("SELECT * FROM yakopi_detail_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$kind_seed_collecting
        ];
    });

    Route::post("/add-kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $tanggal_collecting = $request->tanggal_collecting;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $r_mucronoto = $request->r_mucronoto;
        $r_styloso = $request->r_styloso;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindSeedCollecting = DB::insert("INSERT INTO yakopi_detail_collecting_seed (id_detail_collecting_seed,id_collecting_seed,tanggal_collecting,jumlah_pekerja,r_mucronoto,r_styloso,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$tanggal_collecting,$jumlah_pekerja,$r_mucronoto,$r_styloso,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];
    });

    Route::delete("/delete-kind-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_collecting_seed = $request->id_detail_collecting_seed;
        
        $cekIdCollection = DB::select("SELECT * FROM yakopi_detail_collecting_seed WHERE id_detail_collecting_seed=?",[$id_detail_collecting_seed]);
        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_collecting_seed WHERE id_detail_collecting_seed=?",[$id_detail_collecting_seed]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $photo_seed_collecting = DB::select("SELECT * FROM yakopi_collecting_seed_photo WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$photo_seed_collecting
        ];
    });

    Route::post("/add-photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_photo = $request->keterangan_collecting_seed_photo;
        $link_collecting_seed_photo = $request->link_collecting_seed_photo;
        $file_collecting_seed_photo = $request->file_collecting_seed_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photoSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_photo (id_collecting_seed_photo,id_collecting_seed,keterangan_collecting_seed_photo,link_collecting_seed_photo,file_collecting_seed_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_photo,$link_collecting_seed_photo,$file_collecting_seed_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_photo = $request->id_collecting_seed_photo;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_photo WHERE id_collecting_seed_photo=?",[$id_collecting_seed_photo]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_photo WHERE id_collecting_seed_photo=?",[$id_collecting_seed_photo]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $videoSeedCollecting = DB::select("SELECT * FROM yakopi_collecting_seed_video WHERE id_collecting_seed=?",[$id_collecting_seed]);

        return [
            "success"=>true,
            "data"=>$videoSeedCollecting
        ];
    });

    Route::post("/add-video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_video = $request->keterangan_collecting_seed_video;
        $link_collecting_seed_video = $request->link_collecting_seed_video;
        $file_collecting_seed_video = $request->file_collecting_seed_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $videoSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_video (id_collecting_seed_video,id_collecting_seed,keterangan_collecting_seed_video,link_collecting_seed_video,file_collecting_seed_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_video,$link_collecting_seed_video,$file_collecting_seed_video,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_video = $request->id_collecting_seed_video;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_video WHERE id_collecting_seed_video=?",[$id_collecting_seed_video]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_video WHERE id_collecting_seed_video=?",[$id_collecting_seed_video]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $droneSeedCollecting = DB::select("SELECT * FROM yakopi_collecting_seed_drone WHERE id_collecting_seed=?",[$id_collecting_seed]);
        
        return [
            "success"=>true,
            "data"=>$droneSeedCollecting
        ];

    });

    Route::post("/add-drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;
        $keterangan_collecting_seed_drone = $request->keterangan_collecting_seed_drone;
        $link_collecting_seed_drone = $request->link_collecting_seed_drone;
        $file_collecting_seed_drone = $request->file_collecting_seed_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $droneSeedCollecting = DB::insert("INSERT INTO yakopi_collecting_seed_drone (id_collecting_seed_drone,id_collecting_seed,keterangan_collecting_seed_drone,link_collecting_seed_drone,file_collecting_seed_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id_collecting_seed,$keterangan_collecting_seed_drone,$link_collecting_seed_drone,$file_collecting_seed_drone,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-seed-collecting", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed_drone = $request->id_collecting_seed_drone;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed_drone WHERE id_collecting_seed_drone=?",[$id_collecting_seed_drone]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed_drone WHERE id_collecting_seed_drone=?",[$id_collecting_seed_drone]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-collecting-seed", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_collecting_seed = $request->id_collecting_seed;

        $cekIdCollection = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

        if(count($cekIdCollection)>0){
            $id_collecting_seed = $cekIdCollection[0]->id_collecting_seed;

            $cekStatus = DB::select("SELECT * FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_collecting_seed WHERE id_collecting_seed=?",[$id_collecting_seed]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // SEED COLLECTING END

    // NURSERY ACTIVITY START

    Route::get("/nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$nurseryActivity
        ];

    });

    Route::get("/nursery-activity/{id_nursery_activity}", function (Request $request,$id_nursery_activity){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_nursery_activity=?
        ",[$id_nursery_activity]);

        $detail_nursery_activity = DB::select("
        SELECT * FROM yakopi_detail_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

        return [
            "success"=>true,
            "data"=>$nurseryActivity,
            "detail_nursery_activity"=>$detail_nursery_activity
        ];

    });

    Route::get("/history-nursery-activity/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $nurseryActivity = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_nursery_activity AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$nurseryActivity
        ];
    });

    Route::post("/nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $lat_nursery_activity = $request->coordinate["latitude"];
        $long_nursery_activity = $request->coordinate["longitude"];
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $nurseryActivity = DB::insert(' INSERT INTO yakopi_nursery_activity (id_project,id_provinces,id_cities,id_districts,lat_nursery_activity,long_nursery_activity,nama_desa,nama_dusun,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        ,[$id_project,$id_provinces,$id_cities,$id_districts,$lat_nursery_activity,$long_nursery_activity,$nama_desa,$nama_dusun,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
    
        if($nurseryActivity){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::post("/approve-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $status = 1;

        $approveSeedCollecting = DB::update("UPDATE yakopi_nursery_activity SET status=? WHERE id_nursery_activity=?",[$status,$id_nursery_activity]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $status = 2;

        $rejectSeedCollecting = DB::update("UPDATE yakopi_nursery_activity SET status=? WHERE id_nursery_activity=?",[$status,$id_nursery_activity]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });

    Route::post("/kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;

        $kind_nursery_activity = DB::select("SELECT * FROM yakopi_detail_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

        return [
            "success"=>true,
            "data"=>$kind_nursery_activity
        ];
    });

    Route::post("/add-kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_nursery_activity = $request->id_nursery_activity;
        $tanggal_collecting = $request->tanggal_collecting;
        $jumlah_pekerja = $request->jumlah_pekerja;
        $r_mucronoto = $request->r_mucronoto;
        $r_styloso = $request->r_styloso;
        $r_apiculata = $request->r_apiculata;
        $avicennia_spp = $request->avicennia_spp;
        $ceriops_spp = $request->ceriops_spp;
        $xylocarpus_spp = $request->xylocarpus_spp;
        $bruguiera_spp = $request->bruguiera_spp;
        $sonneratia_spp = $request->sonneratia_spp;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $kindSeedCollecting = DB::insert("INSERT INTO yakopi_detail_nursery_activity (id_detail_nursery_activity,id_nursery_activity,tanggal_collecting,jumlah_pekerja,r_mucronoto,r_styloso,r_apiculata,avicennia_spp,ceriops_spp,xylocarpus_spp,bruguiera_spp,sonneratia_spp,created_by,created_time)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[null,$id_nursery_activity,$tanggal_collecting,$jumlah_pekerja,$r_mucronoto,$r_styloso,$r_apiculata,$avicennia_spp,$ceriops_spp,$xylocarpus_spp,$bruguiera_spp,$sonneratia_spp,$created_by,$created_time]);
        
        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];
    });

    Route::delete("/delete-kind-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_nursery_activity = $request->id_detail_nursery_activity;
        
        $cekId = DB::select("SELECT * FROM yakopi_detail_nursery_activity WHERE id_detail_nursery_activity=?",[$id_detail_nursery_activity]);
        if(count($cekId)>0){
            $id_nursery_activity = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id_nursery_activity]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_nursery_activity WHERE id_detail_nursery_activity=?",[$id_detail_nursery_activity]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $photo = DB::select("SELECT * FROM yakopi_nursery_activity_photo WHERE id_nursery_activity=?",[$id]);

        return [
            "success"=>true,
            "data"=>$photo
        ];
    });

    Route::post("/add-photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_photo;
        $link = $request->link_nursery_activity_photo;
        $file = $request->file_nursery_activity_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $photo = DB::insert("INSERT INTO yakopi_nursery_activity_photo (id_nursery_activity_photo,id_nursery_activity,keterangan_nursery_activity_photo,link_nursery_activity_photo,file_nursery_activity_photo,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-photo-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_photo;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_photo WHERE id_nursery_activity_photo=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_photo WHERE id_nursery_activity_photo=?",[$ic]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $video = DB::select("SELECT * FROM yakopi_nursery_activity_video WHERE id_nursery_activity=?",[$id]);

        return [
            "success"=>true,
            "data"=>$video
        ];
    });

    Route::post("/add-video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_video;
        $link = $request->link_nursery_activity_video;
        $file = $request->file_nursery_activity_video;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $video = DB::insert("INSERT INTO yakopi_nursery_activity_video (id_nursery_activity_video,id_nursery_activity,keterangan_nursery_activity_video,link_nursery_activity_video,file_nursery_activity_video,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-video-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_video;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_video WHERE id_nursery_activity_video=?",[$id_nursery_activity_video]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_video WHERE id_nursery_activity_video=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $drone = DB::select("SELECT * FROM yakopi_nursery_activity_drone WHERE id_nursery_activity=?",[$id]);
        
        return [
            "success"=>true,
            "data"=>$drone
        ];

    });

    Route::post("/add-drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;
        $keterangan = $request->keterangan_nursery_activity_drone;
        $link = $request->link_nursery_activity_drone;
        $file = $request->file_nursery_activity_drone;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $drone = DB::insert("INSERT INTO yakopi_nursery_activity_drone (id_nursery_activity_drone,id_nursery_activity,keterangan_nursery_activity_drone,link_nursery_activity_drone,file_nursery_activity_drone,created_by,created_time)
        VALUES (?,?,?,?,?,?,?)"
        ,[null,$id,$keterangan,$link,$file,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil disimpan"
        ];

    });

    Route::delete("/delete-drone-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity_drone;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity_drone WHERE id_nursery_activity=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity_drone WHERE id_nursery_activity_drone=?",[$id]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::delete("/delete-nursery-activity", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_nursery_activity;

        $cekId = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id]);

        if(count($cekId)>0){
            $id1 = $cekId[0]->id_nursery_activity;

            $cekStatus = DB::select("SELECT * FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_nursery_activity WHERE id_nursery_activity=?",[$id1]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    // NURSERY ACTIVITY END

    // PLANTING ACTION START

    Route::get("/planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_planting_action;

        $planting = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$planting
        ];

    });

    Route::get("/planting-action/{id_planting_action}", function (Request $request,$id_planting_action){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_planting_action=?
        ",[$id_planting_action]);

        $detailPlantingAction = DB::select("
        SELECT * FROM yakopi_detail_planting_action WHERE id_planting_action=?",[$id_planting_action]);

        return [
            "success"=>true,
            "data"=>$plantingAction,
            "detail_planting_action"=>$detailPlantingAction
        ];

    });

    Route::get("/history-planting-action/{id_pengguna}", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $plantingAction = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_planting_action AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.created_by=?
        ",[$json->id_pengguna]);

        return [
            "success"=>true,
            "data"=>$plantingAction
        ];
    });

    Route::post("/planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $jarak_tanam = $request->jarak_tanam;
        $lokasi_tanam = $request->lokasi_tanam;
        $info_1 = $request->info_1;
        $transportation = $request->transportation;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = "";
        $created_by = $json->id_pengguna;  
        $created_time = date("Y-m-d H:i:s");
        $status = 0;

        $plantingAction = DB::insert(" INSERT INTO yakopi_planting_action (id_planting_action,id_project,id_provinces,id_cities,id_districts,nama_desa,nama_dusun,jarak_tanam,lokasi_tanam,info_1,transportation,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status)
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jarak_tanam,$lokasi_tanam,$info_1,$transportation,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);
        if($plantingAction){
            return [
                "success"=>true,
                "msg"=>"Data berhasil ditambahkan"
            ];
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat ditambahkan"
            ];
        }
    });

    Route::post("/approve-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;
        $status = 1;

        $approvePlantingAction = DB::update("UPDATE yakopi_planting_action SET status=? WHERE id_planting_action=?",[$status,$id_planting_action]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Mengkonfirmasi Data"
        ];
    });

    Route::post("/reject-planting-action", function (Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_planting_action = $request->id_planting_action;
        $status = 2;

        $rejectPlantingAction = DB::update("UPDATE yakopi_planting_action SET status=? WHERE id_planting_action=?",[$status,$id_planting_action]);
        return [
            "success"=>true,
            "msg"=>"Berhasil Menolak Data"
        ];
    });
    

        


    // COMMUNITY DEVELOPMENT START

    Route::get("/community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_community_register AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
    
        return [
            "success"=>true,
            "data"=>$community_development
        ];
    });

    Route::get("/community-register/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $community_development = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_community_register AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_community_register=?
        ",[$id]);

        return [
            "success"=>true,
            "data"=>$community_development[0]
        ];
    });
    
    Route::post("/community-register", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $nomor_mou = $request->nomor_mou;
        $nama_kelompok = $request->nama_kelompok;
        $ketua_kelompok = $request->ketua_kelompok;
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $jumlah_site = $request->jumlah_site;
        $jumlah_plot = $request->jumlah_plot;
        $luas_area_mou = $request->luas_area_mou;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";

        $insertcommunity = DB::insert("INSERT INTO yakopi_community_register 
        VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[$nomor_mou,$nama_kelompok,$ketua_kelompok,$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$jumlah_site,$jumlah_plot,$luas_area_mou,$created_by,$created_time,$status]);
    
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });

    Route::delete("/delete-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }
    });

    Route::post("/approve-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_community_register SET status='1' WHERE id_community_register=?",[$id_community_register]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }
    });

    Route::post("/reject-community-register", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_community_register;

        $cekIdCommunity = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id]);

        if(count($cekIdCommunity)>0){
            $id_community_register = $cekIdCommunity[0]->id_community_register;

            $cekStatus = DB::select("SELECT * FROM yakopi_community_register WHERE id_community_register=?",[$id_community_register]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_community_register SET status='2' WHERE id_community_register=?",[$id_community_register]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }

    });
    
    Route::get("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_silvoshery AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");
    
        return [
            "success"=>true,
            "data"=>$silvoshery
        ];
    });

    Route::get("/silvoshery/{id}", function(Request $request, $id){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $silvoshery = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_silvoshery AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_silvoshery=?
        ",[$id]);
    
        return [
            "success"=>true,
            "data"=>$silvoshery[0]
        ];
    });
    
    Route::post("/silvoshery", function(Request $request){
        $token = $request->bearerToken();
    
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
    
        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $kode_silvoshery = $request->kode_silvoshery;
        $pemilik_tambak = $request->pemilik_tambak;
        $jumlah_tanaman = $request->jumlah_tanaman;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";
    
        $insertsilvoshery = DB::insert("INSERT INTO yakopi_silvoshery VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?)",[$id_project,$id_provinces,$id_cities,$id_districts,$nama_desa,$nama_dusun,$kode_silvoshery,$pemilik_tambak,$jumlah_tanaman,$created_by,$created_time,$status]);
        return [
            "success"=>true,
            "msg"=>"Berhasil melakukan pendaftaran"
        ];
    });

    Route::delete("/delete-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){
                
                $delete = DB::delete("DELETE FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }

    });

    Route::post("/approve-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_silvoshery SET status='1' WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }

    });

    Route::post("/reject-silvoshery", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id_silvoshery;

        $cekIdSilvoshery = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id]);

        if(count($cekIdSilvoshery)>0){
            $id_silvoshery = $cekIdSilvoshery[0]->id_silvoshery;

            $cekStatus = DB::select("SELECT * FROM yakopi_silvoshery WHERE id_silvoshery=?",[$id_silvoshery]);

            if($cekStatus[0]->status=="0"){

                $update = DB::update("UPDATE yakopi_silvoshery SET status='2' WHERE id_silvoshery=?",[$id_silvoshery]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil diperbarui"
                ];

            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat diperbarui"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat diperbarui"
            ];
        }
    });

    // COMMUNITY DEVELOPMENT END

    // RESEARCH START

    // GROWTH RESEARCH START

    Route::get("/research/growthResearch", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);
        
        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth_research AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        ");

        return [
            "success"=>true,
            "data"=>$data
        ];
    });

    Route::get("/research/growthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("
        SELECT project.nama_project,provinces.prov_name,cities.city_name,districts.dis_name,la.* FROM yakopi_growth_research AS la
        INNER JOIN yakopi_project AS project ON project.id_project=la.id_project
        INNER JOIN yakopi_provinces AS provinces ON provinces.prov_id=la.id_provinces
        INNER JOIN yakopi_cities AS cities ON cities.city_id=la.id_cities
        INNER JOIN yakopi_districts AS districts ON districts.dis_id=la.id_districts
        WHERE la.id_growth_research=?",[$id]);

        $detail = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_growth_research=?",[$data[0]->id_growth_research]);

        return [
            "success"=>true,
            "data"=>$data,
            "detail"=>$detail
        ];

    });

    Route::post("/research/growthResearch/add", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_project = $request->project;
        $id_provinces = $request->province;
        $id_cities = $request->city;
        $id_districts = $request->district;
        $lat_growth_research = $request->coordinate["latitude"];
        $long_growth_research = $request->coordinate["longitude"];
        $nama_desa = $request->village;
        $nama_dusun = $request->backwood;
        $site_code = $request->site_code;
        $plot_code = $request->plot_code;
        $area = $request->area;
        $spesies = $request->species;
        $jumlah = $request->jumlah;
        $monitoring_ke = $request->monitoring_ke;
        $catatan_1 = $request->catatan_1;
        $catatan_2 = $request->catatan_2;
        $dilaporkan_oleh = $request->dilaporkan_oleh;
        $ttd_pelapor = '';
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");
        $status = "0";

        $insert = DB::insert(" INSERT INTO yakopi_growth_research 
        (id_growth_research,id_project,id_provinces,id_cities,id_districts,lat_growth_research,long_growth_research,nama_desa,nama_dusun,site_code,plot_code,area,spesies,jumlah,monitoring_ke,catatan_1,catatan_2,dilaporkan_oleh,ttd_pelapor,created_by,created_time,status) 
        VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        ,[$id_project,$id_provinces,$id_cities,$id_districts,$lat_growth_research,$long_growth_research,$nama_desa,$nama_dusun,$site_code,$plot_code,$area,$spesies,$jumlah,$monitoring_ke,$catatan_1,$catatan_2,$dilaporkan_oleh,$ttd_pelapor,$created_by,$created_time,$status]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];

    });

    Route::delete("/research/growthResearch/delete/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$id]);
        
        if($data[0]->status=="0"){
            $delete = DB::delete("DELETE FROM yakopi_growth_research WHERE id_growth_research=?",[$id]);

            if($delete){
                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak dapat dihapus"
            ];
        }

    });

    Route::get("/research/dataGrowthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select(" SELECT * FROM yakopi_detail_growth_research WHERE id_growth_research=?",[$id]);

        return [
            "success"=>true,
            "data"=>$data
        ];

    });

    Route::post("/research/growthResearch/addDetail", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_growth_research = $request->id_growth_research;
        $no_tagging = $request->no_tagging;
        $tinggi = $request->tinggi;
        $diameter = $request->diameter;
        $jumlah_daun = $request->jumlah_daun;
        $jumlah_percabangan = $request->jumlah_percabangan;
        $keterangan = $request->keterangan;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $insert = DB::insert(" INSERT INTO yakopi_detail_growth_research
        (id_detail_growth_research,id_growth_research,no_tagging,tinggi,diameter,jumlah_daun,jumlah_percabangan,keterangan,created_by,created_time)
        VALUES (null,?,?,?,?,?,?,?,?,?,?)"
        ,[$id_growth_research,$no_tagging,$tinggi,$diameter,$jumlah_daun,$jumlah_percabangan,$keterangan,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];
    });

    Route::delete("/research/growthResearch/deleteDetail/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $cek = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$id]);

        if(count($cek)>0){
            $cekStatus = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$cek[0]->id_growth_research]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$id]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus karena sudah dilaporkan"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak ditemukan"
            ];
        }
    });

    Route::get("/research/photoDataGrowthResearch/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $data = DB::select("SELECT * FROM yakopi_photo_growth_research WHERE id_detail_growth_research=?",[$id]);

        return [
            "success"=>true,
            "data"=>$data
        ];

    });

    Route::post("/research/growthResearch/addPhoto", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id_detail_growth_research = $request->id_detail_growth_research;
        $link_growth_research_photo = $request->link_growth_research_photo;
        $file_growth_research_photo = $request->file_growth_research_photo;
        $created_by = $json->id_pengguna;
        $created_time = date("Y-m-d H:i:s");

        $insert = DB::insert(" INSERT INTO yakopi_growth_research_photo 
        (id_growth_research_photo,id_detail_growth_research,link_growth_research_photo,file_growth_research_photo,created_by,created_time)
        VALUES (null,?,?,?,?,?,?)"
        ,[$id_detail_growth_research,$link_growth_research_photo,$file_growth_research_photo,$created_by,$created_time]);

        return [
            "success"=>true,
            "msg"=>"Data berhasil ditambahkan"
        ];

    });

    Route::delete("/research/growthResearch/deletePhoto/{id}", function(Request $request){
        $token = $request->bearerToken();
        $parsed = Crypt::decryptString($token);
        $json = json_decode($parsed);

        $id = $request->id;

        $cek = DB::select("SELECT * FROM yakopi_growth_research_photo WHERE id_growth_research_photo=?",[$id]);

        if(count($cek)>0){
            $cekDetail = DB::select("SELECT * FROM yakopi_detail_growth_research WHERE id_detail_growth_research=?",[$cek[0]->id_detail_growth_research]);

            $cekStatus = DB::select("SELECT * FROM yakopi_growth_research WHERE id_growth_research=?",[$cekDetail[0]->id_growth_research]);

            if($cekStatus[0]->status=="0"){
                $delete = DB::delete("DELETE FROM yakopi_growth_research_photo WHERE id_growth_research_photo=?",[$id]);

                return [
                    "success"=>true,
                    "msg"=>"Data berhasil dihapus"
                ];
            }else{
                return [
                    "success"=>false,
                    "msg"=>"Data tidak dapat dihapus karena sudah dilaporkan"
                ];
            }
        }else{
            return [
                "success"=>false,
                "msg"=>"Data tidak ditemukan"
            ];
        }
    });

    // GROWTH RESEARCH END





});
